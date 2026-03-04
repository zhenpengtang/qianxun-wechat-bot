<?php
session_start();
$apiUrl = "http://192.168.66.42:7777/qianxun/httpapi?wxid=wxid_q44zpd46iugs22";
$configDir = "groups_config";

if (!is_dir($configDir)) mkdir($configDir, 0777, true);

// 1. 刷新原始数据
if (isset($_POST['action']) && $_POST['action'] === 'fetch') {
    $payload = json_encode(["type" => "Q0006", "data" => ["type" => "1"]]);
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $resp = curl_exec($ch);
    $res = json_decode($resp, true);
    if (isset($res['result'])) {
        $_SESSION['raw_groups'] = $res['result'];
    }
    header("Location: get_groups.php"); exit;
}

// 2. 保存分类表
if (isset($_POST['action']) && $_POST['action'] === 'save_category') {
    $catName = preg_replace('/[^\w\x{4e00}-\x{9fa5}]/u', '', $_POST['cat_name']); // 仅允许中文数字字母
    $selectedIds = $_POST['groups'] ?? [];
    $raw = $_SESSION['raw_groups'] ?? [];
    
    $saveData = [];
    foreach ($raw as $item) {
        if (in_array($item['wxid'], $selectedIds)) {
            $saveData[] = ['id' => $item['wxid'], 'name' => $item['nick']];
        }
    }
    
    if (!empty($catName) && !empty($saveData)) {
        file_put_contents("$configDir/{$catName}.json", json_encode($saveData, JSON_UNESCAPED_UNICODE));
        $_SESSION['msg'] = "分类 [{$catName}] 已保存！包含 " . count($saveData) . " 个群。";
    }
    header("Location: get_groups.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>群组分类管理器</title>
    <style>
        .group-box { max-height: 600px; overflow-y: auto; background: #fff; border-radius: 8px; }
        body { background: #f4f7f6; }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h3>📂 群组分类管理</h3>
                <div>
                    <a href="sender.php" class="btn btn-dark">去群发消息</a>
                    <form method="POST" class="d-inline"><input type="hidden" name="action" value="fetch"><button class="btn btn-primary">拉取最新群聊</button></form>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="save_category">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div class="input-group w-50">
                        <span class="input-group-text">分类名称</span>
                        <input type="text" name="cat_name" class="form-control" placeholder="例如：兼职群表1" required>
                    </div>
                    <button type="submit" class="btn btn-success">保存选中群组到新表</button>
                </div>
                <div class="card-body group-box p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50"><input type="checkbox" onclick="toggleAll(this)"></th>
                                <th>群名称</th>
                                <th>微信ID (wxid)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $raw = $_SESSION['raw_groups'] ?? [];
                            foreach ($raw as $g): 
                                if(strpos($g['wxid'], '@chatroom') === false) continue;
                            ?>
                            <tr>
                                <td><input type="checkbox" name="groups[]" value="<?= $g['wxid'] ?>" class="g-chk"></td>
                                <td class="fw-bold"><?= htmlspecialchars($g['nick']) ?></td>
                                <td class="text-muted small"><?= $g['wxid'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
    <script>
        function toggleAll(el) { document.querySelectorAll('.g-chk').forEach(c => c.checked = el.checked); }
    </script>
</body>
</html>
