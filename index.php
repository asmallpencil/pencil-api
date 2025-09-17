<?php
// index.php - 完整的PHP检测工具

// 处理检测请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $url = $_POST['url'] ?? '';
    
    if (empty($url)) {
        echo json_encode([
            'success' => false,
            'error' => '请输入要检测的URL'
        ]);
        exit;
    }
    
    // 验证URL格式
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode([
            'success' => false,
            'error' => '无效的URL格式'
        ]);
        exit;
    }
    
    // 添加http://前缀（如果没有）
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'http://' . $url;
    }
    
    // 生成唯一的测试文件名
    $testFile = 'php_test_' . uniqid() . '.php';
    $testUrl = rtrim($url, '/') . '/' . $testFile;
    
    // 创建测试PHP文件内容
    $testContent = '<?php echo "PHP_SUPPORT_TEST:" . phpversion(); ?>';
    
    // 尝试上传测试文件（模拟）
    $uploadSuccess = false;
    $response = '';
    
    // 使用file_get_contents检测（更实际的方法）
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'User-Agent: PHP-Detector/1.0'
        ]
    ]);
    
    // 首先尝试访问一个常见的PHP文件
    $commonPhpFiles = ['index.php', 'test.php', 'info.php', 'phpinfo.php'];
    $phpSupported = false;
    $phpVersion = '';
    
    foreach ($commonPhpFiles as $phpFile) {
        $testUrl = rtrim($url, '/') . '/' . $phpFile;
        try {
            $response = @file_get_contents($testUrl, false, $context);
            if ($response !== false) {
                // 检查是否包含PHP特征
                if (preg_match('/<?php/i', $response) || 
                    stripos($response, 'php') !== false ||
                    stripos($response, 'zend') !== false) {
                    $phpSupported = true;
                    break;
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    // 尝试检测服务器响应头
    $headers = @get_headers($url, 1);
    $serverInfo = '';
    if ($headers) {
        foreach ($headers as $key => $value) {
            if (stripos($key, 'server') !== false) {
                $serverInfo = $value;
                if (stripos($value, 'php') !== false || 
                    stripos($value, 'apache') !== false ||
                    stripos($value, 'nginx') !== false) {
                    $phpSupported = true;
                }
            }
        }
    }
    
    // 综合判断
    $result = [
        'success' => true,
        'url' => $url,
        'php_supported' => $phpSupported,
        'server_info' => $serverInfo,
        'response_snippet' => substr($response, 0, 200),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP支持检测工具</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        
        .result.success {
            background: #e8f5e9;
            border: 1px solid #4caf50;
        }
        
        .result.error {
            background: #ffebee;
            border: 1px solid #f44336;
        }
        
        .result.info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
        }
        
        .result h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .result.success h3 {
            color: #2e7d32;
        }
        
        .result.error h3 {
            color: #c62828;
        }
        
        .result.info h3 {
            color: #1565c0;
        }
        
        .result p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .loading {
            text-align: center;
            margin-top: 20px;
            display: none;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .features {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }
        
        .features h4 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .features ul {
            list-style: none;
            color: #666;
        }
        
        .features li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4caf50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐘 PHP支持检测工具</h1>
        <p class="subtitle">检测网站是否支持动态PHP</p>
        
        <form id="detectForm">
            <div class="input-group">
                <label for="urlInput">请输入要检测的网站URL：</label>
                <input 
                    type="text" 
                    id="urlInput" 
                    placeholder="例如：example.com 或 https://example.com"
                    required
                >
            </div>
            <button type="submit" id="detectBtn">开始检测</button>
        </form>
        
        <div class="loading" id="loading">
            <div class="loading-spinner"></div>
            <p style="margin-top: 10px; color: #666;">正在检测中，请稍候...</p>
        </div>
        
        <div id="result" class="result"></div>
        
        <div class="features">
            <h4>功能特点：</h4>
            <ul>
                <li>快速检测网站是否支持PHP</li>
                <li>识别服务器类型和响应头信息</li>
                <li>检测常见的PHP文件访问</li>
                <li>提供详细的检测报告</li>
                <li>完全免费使用</li>
            </ul>
        </div>
    </div>

    <script>
        document.getElementById('detectForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const urlInput = document.getElementById('urlInput');
            const detectBtn = document.getElementById('detectBtn');
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            // 验证输入
            let url = urlInput.value.trim();
            if (!url) {
                showResult('请输入要检测的URL', 'error');
                return;
            }
            
            // 显示加载状态
            loading.style.display = 'block';
            result.style.display = 'none';
            detectBtn.disabled = true;
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `url=${encodeURIComponent(url)}`
                });
                
                const data = await response.json();
                
                loading.style.display = 'none';
                detectBtn.disabled = false;
                
                if (data.success) {
                    let message = '';
                    let type = '';
                    
                    if (data.php_supported) {
                        message = `
                            <h3>✅ 检测到PHP支持</h3>
                            <p><strong>检测URL：</strong>${data.url}</p>
                            <p><strong>服务器信息：</strong>${data.server_info || '未获取到'}</p>
                            <p><strong>响应片段：</strong>${data.response_snippet || '无响应内容'}</p>
                            <p><strong>检测时间：</strong>${data.timestamp}</p>
                        `;
                        type = 'success';
                    } else {
                        message = `
                            <h3>❌ 未检测到PHP支持</h3>
                            <p><strong>检测URL：</strong>${data.url}</p>
                            <p><strong>服务器信息：</strong>${data.server_info || '未获取到'}</p>
                            <p><strong>响应片段：</strong>${data.response_snippet || '无响应内容'}</p>
                            <p><strong>检测时间：</strong>${data.timestamp}</p>
                            <p><strong>提示：</strong>这并不一定表示该网站完全不支持PHP，可能只是无法通过外部检测确认。</p>
                        `;
                        type = 'info';
                    }
                    
                    result.className = `result ${type}`;
                    result.innerHTML = message;
                    result.style.display = 'block';
                } else {
                    showResult(data.error || '检测失败', 'error');
                }
            } catch (error) {
                loading.style.display = 'none';
                detectBtn.disabled = false;
                showResult('检测过程中发生错误，请稍后重试', 'error');
            }
        });
        
        function showResult(message, type) {
            const result = document.getElementById('result');
            result.className = `result ${type}`;
            result.innerHTML = `<h3>${type === 'error' ? '❌ 错误' : 'ℹ️ 信息'}</h3><p>${message}</p>`;
            result.style.display = 'block';
        }
        
        // 回车键提交
        document.getElementById('urlInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('detectForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>
