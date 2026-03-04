<?php
session_start();
date_default_timezone_set('PRC');

$configDir = "groups_config";
$logFile = "history.log";
$apiUrl = "http://192.168.66.42:7777/qianxun/httpapi?wxid=wxid_q44zpd46iugs22";

// --- 接口：获取分类下的群组 (用于 JS 加载) ---
if (isset($_GET['get_cat_detail'])) {
    header('Content-Type: application/json');
    $file = $_GET['get_cat_detail'];
    echo file_exists($file) ? file_get_contents($file) : json_encode([]);
    exit;
}

// --- 核心：后台执行逻辑 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $targetGroups = $_POST['groups'] ?? [];
    $message = $_POST['message'] ?? '';

    // 1. 告诉浏览器“收到了”，然后断开连接
    ob_start();
    echo json_encode(["status" => "running"]); // 给前端一个即时响应
    header('Content-Length: '.ob_get_length());
    header('Connection: close');
    ob_end_flush();
    ob_flush();
    flush();
    
    // 如果是 PHP-FPM 模式，这行非常关键
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // 2. 浏览器已经断开了，下面开始在后台偷偷干活
    ignore_user_abort(true); // 即使浏览器关了也继续运行
    set_time_limit(0);       // 运行时间无上限

    foreach ($targetGroups as $index => $wxid) {
        $payload = json_encode(["type" => "Q0001", "data" => ["wxid" => $wxid, "msg" => $message]]);
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $resp = curl_exec($ch);
        $res = json_decode($resp, true);
        curl_close($ch);

        // 写入精确到群的日志
        $status = (isset($res['code']) && $res['code'] == 200) ? '成功' : '失败';
        $logLine = date('Y-m-d H:i:s') . "|" . $wxid . "|" . $status . PHP_EOL;
        file_put_contents($logFile, $logLine, FILE_APPEND);

        // 随机延迟
        if ($index < count($targetGroups) - 1) {
            sleep(rand(5, 60));
        }
    }
    exit; // 后台任务结束
}

// 获取分类
$categories = glob("$configDir/*.json");
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>AI 消息后台控制台</title>
    <style>
        .log-box { font-family: monospace; font-size: 0.8rem; background: #1e293b; color: #38bdf8; height: 400px; overflow-y: auto; padding: 15px; border-radius: 8px; }
        .success-text { color: #4ade80; }
        .fail-text { color: #f87171; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 p-4">
                <h5 class="fw-bold mb-3">🚀 发布后台任务</h5>
                <form id="sendForm">
                    <div class="mb-3">
                        <select id="catSelect" class="form-select" onchange="loadGroups(this.value)">
                            <option value="">-- 选择群分类 --</option>
                            <?php foreach ($categories as $file): ?>
                                <option value="<?= htmlspecialchars($file) ?>"><?= basename($file, '.json') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="groupList" class="mb-3 border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto; display:none;"></div>
                    <textarea id="msg" class="form-control mb-3" rows="6" placeholder="消息内容..."></textarea>
                    <button type="button" onclick="startTask()" class="btn btn-primary w-100 fw-bold">启动任务 (可关闭页面)</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">📊 发送状态记录</h5>
                    <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary">手动刷新</button>
                </div>
                <div id="logContent" class="log-box">
                    <?php 
                    if(file_exists($logFile)) {
                        $logs = array_reverse(file($logFile));
                        foreach($logs as $line) {
                            $parts = explode('|', $line);
                            if(count($parts) < 3) continue;
                            $stClass = (trim($parts[2]) == '成功') ? 'success-text' : 'fail-text';
                            echo "<div>[{$parts[0]}] ID: {$parts[1]} <span class='{$stClass}'>-- " . trim($parts[2]) . "</span></div>";
                        }
                    } else {
                        echo "暂无记录";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadGroups(file) {
    if(!file) return;
    fetch('?get_cat_detail=' + encodeURIComponent(file))
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById('groupList');
            box.innerHTML = data.map(g => `
                <div class='small'><input type='checkbox' class='g-item' value='${g.id}' checked> ${g.name}</div>
            `).join('');
            box.style.display = 'block';
        });
}

function startTask() {
    const checked = Array.from(document.querySelectorAll('.g-item:checked')).map(i => i.value);
    const msg = document.getElementById('msg').value;
    if(checked.length === 0 || !msg) return alert('请选择群组和填写内容');

    if(!confirm('任务将在服务器后台运行，即使关闭此页面也会继续。确定？')) return;

    // 使用 FormData 发送异步请求
    let fd = new FormData();
    fd.append('action', 'send');
    fd.append('message', msg);
    checked.forEach(id => fd.append('groups[]', id));

    fetch('', { method: 'POST', body: fd });
    
    alert('✅ 任务已提交给服务器后台！\n系统将自动以 5-60s 间隔发送。\n你可以刷新右侧查看记录。');
    setTimeout(() => location.reload(), 1000);
}
</script>
</body>
</html>
