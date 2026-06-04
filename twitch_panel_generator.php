<?php
/**
 * Twitch Panel Generator
 * 1ファイル完結型 画像+テキスト合成ツール
 */

// ===== 設定定数 =====
define('MAX_IMAGE_HEIGHT', 1000);  // 画像の最大高さ
define('MAX_FONT_SIZE', 100);      // フォントサイズの最大値
define('TEXT_PADDING', 1);         // テキスト余白
define('TARGET_WIDTH', 320);       // Twitchパネルの横幅

// ===== ディレクトリ設定 =====
define('DIR_FONTS', __DIR__ . '/uploads/fonts/');

// ディレクトリ作成
if (!is_dir(DIR_FONTS)) {
    mkdir(DIR_FONTS, 0777, true);
}

// ===== 標準フォント定義 =====
$defaultFonts = [
    'Arial' => [
        'paths' => [
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            '/usr/share/fonts/TTF/Arial.ttf',
            '/Library/Fonts/Arial.ttf',
            '/Library/Fonts/Microsoft/Arial.ttf',
        ],
        'download_url' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/roboto/Roboto-Regular.ttf',
        'filename' => 'Roboto-Regular.ttf'
    ],
    'Times New Roman' => [
        'paths' => [
            'C:/Windows/Fonts/times.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Times_New_Roman.ttf',
            '/Library/Fonts/Times New Roman.ttf',
        ],
        'download_url' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/playfairdisplay/PlayfairDisplay-Regular.ttf',
        'filename' => 'PlayfairDisplay-Regular.ttf'
    ],
    'Courier New' => [
        'paths' => [
            'C:/Windows/Fonts/cour.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Courier_New.ttf',
            '/Library/Fonts/Courier New.ttf',
        ],
        'download_url' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/courierprime/CourierPrime-Regular.ttf',
        'filename' => 'CourierPrime-Regular.ttf'
    ],
    'Verdana' => [
        'paths' => [
            'C:/Windows/Fonts/verdana.ttf',
            '/Library/Fonts/Verdana.ttf',
        ],
        'download_url' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/inter/static/Inter-Regular.ttf',
        'filename' => 'Inter-Regular.ttf'
    ],
    'Meiryo' => [
        'paths' => [
            'C:/Windows/Fonts/meiryo.ttc',
            '/usr/share/fonts/truetype/fonts-japanese-gothic.ttf',
            '/usr/share/fonts/ja/TrueType/kochi-gothic-subst.ttf',
            '/Library/Fonts/Supplemental/Meiryo.ttc',
        ],
        'download_url' => 'https://raw.githubusercontent.com/googlefonts/noto-cjk/main/Sans/SubsetOTF/JP/NotoSansJP-Regular.otf',
        'filename' => 'NotoSansJP-Regular.otf'
    ],
];

// ===== Ajax処理 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // プレビュー生成
    if (isset($_POST['action']) && $_POST['action'] === 'preview') {
        $result = generateImage($_POST, $_FILES);
        if ($result['success']) {
            echo json_encode(['success' => true, 'image' => base64_encode($result['data'])]);
        } else {
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
        exit;
    }
    
    // 画像保存
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $result = generateImage($_POST, $_FILES);
        if ($result['success']) {
            $filename = !empty($_POST['filename']) ? $_POST['filename'] : 'panel_' . date('YmdHis');
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename) . '.png';
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($result['data']));
            echo $result['data'];
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $result['error']]);
            exit;
        }
    }
}

// ===== 画像生成関数 =====
function generateImage($params, $files) {
    // 画像ファイルの取得
    $imageFile = '';
    if (isset($files['bg_image']) && $files['bg_image']['error'] === UPLOAD_ERR_OK) {
        $imageFile = $files['bg_image']['tmp_name'];
    }
    
    $text = $params['text'] ?? '';
    
    // 本文フォントファイルの取得
    $customFontFile = '';
    if (isset($files['custom_font']) && $files['custom_font']['error'] === UPLOAD_ERR_OK) {
        $customFontFile = $files['custom_font']['tmp_name'];
    }
    $fontPath = getFontPath($params['font'] ?? 'Arial', $customFontFile);
    
    $fontSize = intval($params['font_size'] ?? 20);
    $lineHeight = intval($params['line_height'] ?? ($fontSize * 1.4));
    $wrapLineHeight = intval($params['wrap_line_height'] ?? ($fontSize * 1.2));
    $textX = intval($params['text_x'] ?? 0);
    $textY = intval($params['text_y'] ?? 0);
    $textColor = $params['text_color'] ?? '#FFFFFF';
    $textAlign = $params['text_align'] ?? 'left';
    $outlineEnabled = isset($params['outline_enabled']) && $params['outline_enabled'] === 'true';
    $outlineColor = $params['outline_color'] ?? '#000000';
    $outlineWidth = intval($params['outline_width'] ?? 2);
    $heightMode = $params['height_mode'] ?? 'auto';
    $manualHeight = intval($params['manual_height'] ?? 0);
    
    // ヘッダ設定パラメータ
    $headerEnabled = isset($params['header_enabled']) && $params['header_enabled'] === 'true';
    $headerImageFile = '';
    if (isset($files['header_image']) && $files['header_image']['error'] === UPLOAD_ERR_OK) {
        $headerImageFile = $files['header_image']['tmp_name'];
    }
    $headerText = $params['header_text'] ?? '';
    $headerFontParam = $params['header_font'] ?? 'Arial';
    
    $headerCustomFontFile = '';
    if (isset($files['header_custom_font']) && $files['header_custom_font']['error'] === UPLOAD_ERR_OK) {
        $headerCustomFontFile = $files['header_custom_font']['tmp_name'];
    }
    
    $headerFontSize = intval($params['header_font_size'] ?? 24);
    $headerTextAlign = $params['header_text_align'] ?? 'center';
    $headerTextColor = $params['header_text_color'] ?? '#FFFFFF';
    $headerOutlineEnabled = isset($params['header_outline_enabled']) && $params['header_outline_enabled'] === 'true';
    $headerOutlineColor = $params['header_outline_color'] ?? '#000000';
    $headerOutlineWidth = intval($params['header_outline_width'] ?? 2);
    
    // 画像読み込み
    if (empty($imageFile) || !file_exists($imageFile)) {
        return ['success' => false, 'error' => '背景画像が選択されていません。'];
    }

    // ヘッダ画像の読み込みとリサイズ
    $headerHeight = 0;
    $resizedHeaderImage = null;
    
    if ($headerEnabled && !empty($headerImageFile) && file_exists($headerImageFile)) {
        $headerInfo = getimagesize($headerImageFile);
        $headerMime = $headerInfo['mime'];
        switch ($headerMime) {
            case 'image/jpeg':
                $headerSrc = @imagecreatefromjpeg($headerImageFile);
                break;
            case 'image/png':
                $headerSrc = @imagecreatefrompng($headerImageFile);
                break;
            case 'image/gif':
                $headerSrc = @imagecreatefromgif($headerImageFile);
                break;
            default:
                $headerSrc = null;
                break;
        }
        if ($headerSrc) {
            $hOrigWidth = imagesx($headerSrc);
            $hOrigHeight = imagesy($headerSrc);
            $headerHeight = intval($hOrigHeight * (TARGET_WIDTH / $hOrigWidth));
            
            $resizedHeaderImage = imagecreatetruecolor(TARGET_WIDTH, $headerHeight);
            imagealphablending($resizedHeaderImage, false);
            imagesavealpha($resizedHeaderImage, true);
            imagecopyresampled($resizedHeaderImage, $headerSrc, 0, 0, 0, 0, TARGET_WIDTH, $headerHeight, $hOrigWidth, $hOrigHeight);
            imagedestroy($headerSrc);
        }
    }

    // テキストがある場合のみフォントの存在チェック
    if (!empty($text)) {
        if (empty($fontPath) || !file_exists($fontPath)) {
            return [
                'success' => false,
                'error' => 'フォントファイルが見つかりません。フォントファイル（.ttf / .otf / .ttc）を追加して選択してください。'
            ];
        }
    }
    
    $imageInfo = getimagesize($imageFile);
    $mimeType = $imageInfo['mime'];
    
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($imageFile);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($imageFile);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($imageFile);
            break;
        default:
            return ['success' => false, 'error' => 'Unsupported image type'];
    }
    
    // 320pxにリサイズ
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);
    $newWidth = TARGET_WIDTH;
    $newHeight = intval($originalHeight * ($newWidth / $originalWidth));
    
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($resizedImage, false);
    imagesavealpha($resizedImage, true);
    imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    imagedestroy($sourceImage);
    
    // テキスト処理
    $lines = explode("\n", $text);
    $paragraphs = [];
    
    foreach ($lines as $line) {
        $words = mb_str_split($line, 1);
        $currentLine = '';
        $wrapped = [];
        
        foreach ($words as $char) {
            $testLine = $currentLine . $char;
            $bbox = @imagettfbbox($fontSize, 0, $fontPath, $testLine);
            if ($bbox === false) {
                return [
                    'success' => false,
                    'error' => 'フォントの読み込みまたはテキストサイズ計算に失敗しました。フォントファイルが破損している可能性があります。'
                ];
            }
            $textWidth = $bbox[2] - $bbox[0];
            
            if ($textWidth > (TARGET_WIDTH - TEXT_PADDING * 2)) {
                if ($currentLine !== '') {
                    $wrapped[] = $currentLine;
                }
                $currentLine = $char;
            } else {
                $currentLine = $testLine;
            }
        }
        
        if ($currentLine !== '') {
            $wrapped[] = $currentLine;
        }
        
        $paragraphs[] = $wrapped;
    }
    
    // 必要な高さを計算
    $currentY = $textY + $fontSize;
    foreach ($paragraphs as $pIndex => $wrapped) {
        foreach ($wrapped as $lIndex => $line) {
            if ($pIndex === count($paragraphs) - 1 && $lIndex === count($wrapped) - 1) {
                break;
            }
            if ($lIndex < count($wrapped) - 1) {
                $currentY += $wrapLineHeight;
            } else {
                $currentY += $lineHeight;
            }
        }
    }
    $requiredHeight = $currentY + ($fontSize * 0.5) + TEXT_PADDING;
    
    if ($heightMode === 'manual' && $manualHeight > 0) {
        $bodyHeight = min($manualHeight, MAX_IMAGE_HEIGHT);
    } else {
        $bodyHeight = min(max($newHeight, $requiredHeight), MAX_IMAGE_HEIGHT);
    }
    
    $finalHeight = $bodyHeight + $headerHeight;
    
    // 最終画像作成
    $finalImage = imagecreatetruecolor($newWidth, $finalHeight);
    imagealphablending($finalImage, false);
    imagesavealpha($finalImage, true);
    
    // 1. ヘッダ画像をコピー
    if ($resizedHeaderImage) {
        imagecopy($finalImage, $resizedHeaderImage, 0, 0, 0, 0, TARGET_WIDTH, $headerHeight);
        imagedestroy($resizedHeaderImage);
    }
    
    // 2. 本文背景画像をタイル状に配置
    for ($y = $headerHeight; $y < $finalHeight; $y += $newHeight) {
        $copyHeight = min($newHeight, $finalHeight - $y);
        imagecopy($finalImage, $resizedImage, 0, $y, 0, 0, $newWidth, $copyHeight);
    }
    
    imagedestroy($resizedImage);
    
    // テキスト描画用アルファブレンドの有効化
    imagealphablending($finalImage, true);

    // 1. ヘッダテキストの描画 (上下中央に自動配置)
    if ($headerEnabled && !empty($headerText)) {
        $hFontPath = getFontPath($headerFontParam, $headerCustomFontFile);
        if (!empty($hFontPath) && file_exists($hFontPath)) {
            $hRgb = hexToRgb($headerTextColor);
            $hColor = imagecolorallocate($finalImage, $hRgb[0], $hRgb[1], $hRgb[2]);
            
            $hOutlineColorRgb = hexToRgb($headerOutlineColor);
            $hOutlineColorObj = imagecolorallocate($finalImage, $hOutlineColorRgb[0], $hOutlineColorRgb[1], $hOutlineColorRgb[2]);
            
            // テキスト範囲の計算
            $hBbox = @imagettfbbox($headerFontSize, 0, $hFontPath, $headerText);
            if ($hBbox !== false) {
                $hTextWidth = $hBbox[2] - $hBbox[0];
                
                // 水平X座標の決定
                switch ($headerTextAlign) {
                    case 'center':
                        $hDrawX = (TARGET_WIDTH - $hTextWidth) / 2;
                        break;
                    case 'right':
                        $hDrawX = TARGET_WIDTH - $hTextWidth - TEXT_PADDING;
                        break;
                    default: // left
                        $hDrawX = TEXT_PADDING;
                        break;
                }
                
                // 垂直上下中央Y座標の計算
                $hTextHeight = $hBbox[1] - $hBbox[7];
                $hDrawY = ($headerHeight - $hTextHeight) / 2 - $hBbox[7];
                
                // 縁取り描画
                if ($headerOutlineEnabled) {
                    for ($ox = -$headerOutlineWidth; $ox <= $headerOutlineWidth; $ox++) {
                        for ($oy = -$headerOutlineWidth; $oy <= $headerOutlineWidth; $oy++) {
                            if ($ox != 0 || $oy != 0) {
                                @imagettftext($finalImage, $headerFontSize, 0, $hDrawX + $ox, $hDrawY + $oy, $hOutlineColorObj, $hFontPath, $headerText);
                            }
                        }
                    }
                }
                
                // テキスト描画
                @imagettftext($finalImage, $headerFontSize, 0, $hDrawX, $hDrawY, $hColor, $hFontPath, $headerText);
            }
        }
    }

    // 2. 本文テキストの描画
    if (!empty($text)) {
        $rgb = hexToRgb($textColor);
        $color = imagecolorallocate($finalImage, $rgb[0], $rgb[1], $rgb[2]);
        
        $outlineColorRgb = hexToRgb($outlineColor);
        $outlineColorObj = imagecolorallocate($finalImage, $outlineColorRgb[0], $outlineColorRgb[1], $outlineColorRgb[2]);
        
        $currentY = $headerHeight + $textY + $fontSize;
        
        foreach ($paragraphs as $pIndex => $wrapped) {
            foreach ($wrapped as $lIndex => $line) {
                $bbox = @imagettfbbox($fontSize, 0, $fontPath, $line);
                if ($bbox === false) {
                    return [
                        'success' => false,
                        'error' => 'テキストサイズ計算に失敗しました。'
                    ];
                }
                $textWidth = $bbox[2] - $bbox[0];
                
                // 配置計算
                switch ($textAlign) {
                    case 'center':
                        $drawX = ($newWidth - $textWidth) / 2;
                        break;
                    case 'right':
                        $drawX = $newWidth - $textWidth - TEXT_PADDING;
                        break;
                    default: // left
                        $drawX = $textX + TEXT_PADDING;
                        break;
                }
                
                // 縁取り描画
                if ($outlineEnabled) {
                    for ($ox = -$outlineWidth; $ox <= $outlineWidth; $ox++) {
                        for ($oy = -$outlineWidth; $oy <= $outlineWidth; $oy++) {
                            if ($ox != 0 || $oy != 0) {
                                @imagettftext($finalImage, $fontSize, 0, $drawX + $ox, $currentY + $oy, $outlineColorObj, $fontPath, $line);
                            }
                        }
                    }
                }
                
                // テキスト描画
                $drawResult = @imagettftext($finalImage, $fontSize, 0, $drawX, $currentY, $color, $fontPath, $line);
                if ($drawResult === false) {
                    return [
                        'success' => false,
                        'error' => 'テキストの描画（imagettftext）に失敗しました。GDライブラリやFreeTypeの設定、またはフォントファイルを確認してください。'
                    ];
                }
                
                // 次の行への移動
                if ($lIndex < count($wrapped) - 1) {
                    $currentY += $wrapLineHeight;
                } else {
                    $currentY += $lineHeight;
                }
            }
        }
    }
    
    // メモリ上でPNGデータを生成して取得
    ob_start();
    imagepng($finalImage);
    $imageData = ob_get_clean();
    imagedestroy($finalImage);
    
    return ['success' => true, 'data' => $imageData];
}

// ===== ヘルパー関数 =====
function getFontPath($font, $customFontFile = '') {
    global $defaultFonts;
    
    // 1. アップロードされたフォントファイルの確認
    if (!empty($customFontFile) && file_exists($customFontFile)) {
        return $customFontFile;
    }
    
    // 2. デフォルトフォントの探索
    if (isset($defaultFonts[$font])) {
        // 定義されたローカルパスを順に探索
        foreach ($defaultFonts[$font]['paths'] as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }
        
        // 3. ローカルで見つからない場合、自動ダウンロードを試みる
        $cachedFont = DIR_FONTS . $defaultFonts[$font]['filename'];
        if (file_exists($cachedFont) && is_file($cachedFont)) {
            return $cachedFont;
        }
        
        // ダウンロード処理
        if (!empty($defaultFonts[$font]['download_url'])) {
            $url = $defaultFonts[$font]['download_url'];
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'follow_location' => true,
                    'user_agent' => 'TwitchPanelGenerator/1.0'
                ]
            ]);
            $fontData = @file_get_contents($url, false, $ctx);
            if ($fontData !== false) {
                if (@file_put_contents($cachedFont, $fontData) !== false) {
                    return $cachedFont;
                }
            }
        }
    }
    
    // 4. 完全にフォントが利用できない場合は、定義上の最初のパスを返却
    if (isset($defaultFonts['Arial'])) {
        return $defaultFonts['Arial']['paths'][0];
    }
    
    return '';
}

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitch Panel Generator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #9146ff 0%, #6441a5 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 30px; }
        .panel { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; }
        .panel h2 { color: #333; margin-bottom: 20px; font-size: 1.3em; border-bottom: 2px solid #9146ff; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; font-weight: 600; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="color"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; font-family: inherit; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .color-input-group { display: flex; gap: 10px; }
        .color-input-group input[type="color"] { width: 60px; height: 40px; border: none; cursor: pointer; }
        .color-input-group input[type="text"] { flex: 1; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .btn { padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #9146ff; color: white; }
        .btn-primary:hover { background: #7c3aed; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .file-upload { display: flex; gap: 10px; align-items: center; }
        .file-upload input[type="file"] { display: none; }
        .preview-container { background: #fff; border: 2px dashed #ddd; border-radius: 8px; padding: 20px; text-align: center; min-height: 400px; display: flex; align-items: center; justify-content: center; }
        .preview-container img { max-width: 100%; border-radius: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .preview-placeholder { color: #999; font-size: 1.2em; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; }
        .button-group .btn { flex: 1; }
        @media (max-width: 768px) { .content { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Twitch Panel Generator</h1>
            <p>画像にテキストを重ねてTwitchパネルを作成（サーバー非保存モード）</p>
        </div>
        
        <div class="content">
            <!-- 左側：設定パネル -->
            <div>
                <!-- 画像選択 -->
                <div class="panel">
                    <h2>📷 画像選択</h2>
                    <div class="form-group">
                        <label>選択中の背景画像</label>
                        <select id="imageSelect">
                            <option value="">-- まずは画像を追加してください --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>背景画像をブラウザに追加</label>
                        <div class="file-upload">
                            <input type="file" id="imageUpload" accept="image/*">
                            <button class="btn btn-secondary" onclick="document.getElementById('imageUpload').click()">ファイルを選択</button>
                            <span id="imageUploadStatus" style="font-size: 12px; color: #666;"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Header 画像設定 -->
                <div class="panel">
                    <h2>🖼️ Header 画像設定</h2>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="headerEnabled">
                            <label for="headerEnabled">ヘッダ画像を有効にする</label>
                        </div>
                    </div>
                    <div id="headerSettingsArea" style="display: none;">
                        <div class="form-group">
                            <label>選択中のヘッダ画像</label>
                            <select id="headerImageSelect">
                                <option value="">-- まずは画像を追加してください --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ヘッダ画像をブラウザに追加</label>
                            <div class="file-upload">
                                <input type="file" id="headerImageUpload" accept="image/*">
                                <button class="btn btn-secondary" onclick="document.getElementById('headerImageUpload').click()">ファイルを選択</button>
                                <span id="headerImageUploadStatus" style="font-size: 12px; color: #666;"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>ヘッダテキスト</label>
                            <input type="text" id="headerTextContent" placeholder="ヘッダテキストを入力...">
                        </div>
                        <div class="form-group">
                            <label>フォント</label>
                            <select id="headerFontSelect">
                                <option value="Arial">Arial</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Meiryo">Meiryo (日本語)</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>フォントサイズ (px)</label>
                                <input type="number" id="headerFontSize" value="24" min="1" max="100">
                            </div>
                            <div class="form-group">
                                <label>配置</label>
                                <select id="headerTextAlign">
                                    <option value="center">中央揃え</option>
                                    <option value="left">左揃え</option>
                                    <option value="right">右揃え</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>テキスト色</label>
                                <div class="color-input-group">
                                    <input type="color" id="headerTextColorPicker" value="#ffffff">
                                    <input type="text" id="headerTextColor" value="#ffffff" placeholder="#ffffff">
                                </div>
                            </div>
                            <div class="form-group">
                                <!-- 空きスペース -->
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="headerOutlineEnabled">
                                <label for="headerOutlineEnabled">縁取りを有効にする</label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>縁取り色</label>
                                <div class="color-input-group">
                                    <input type="color" id="headerOutlineColorPicker" value="#000000">
                                    <input type="text" id="headerOutlineColor" value="#000000" placeholder="#000000">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>縁取りの太さ (px)</label>
                                <input type="number" id="headerOutlineWidth" value="2" min="1" max="10">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- フォント選択 -->
                <div class="panel">
                    <h2>🔤 フォント選択</h2>
                    <div class="form-group">
                        <label>本文フォント</label>
                        <select id="fontSelect">
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Meiryo">Meiryo (日本語)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>カスタムフォントをブラウザに追加 (.ttf, .otf, .ttc)</label>
                        <div class="file-upload">
                            <input type="file" id="fontUpload" accept=".ttf,.otf,.ttc">
                            <button class="btn btn-secondary" onclick="document.getElementById('fontUpload').click()">ファイルを選択</button>
                            <span id="fontUploadStatus" style="font-size: 12px; color: #666;"></span>
                        </div>
                    </div>
                </div>
                
                <!-- テキスト設定 -->
                <div class="panel">
                    <h2>✏️ テキスト設定</h2>
                    <div class="form-group">
                        <label>テキスト</label>
                        <textarea id="textContent" placeholder="ここにテキストを入力..."></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>フォントサイズ (px)</label>
                            <input type="number" id="fontSize" value="20" min="1" max="<?= MAX_FONT_SIZE ?>">
                        </div>
                        <div class="form-group">
                            <!-- 空きスペース -->
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>折り返し行間 (px)</label>
                            <input type="number" id="wrapLineHeight" value="24" min="1" max="200">
                        </div>
                        <div class="form-group">
                            <label>段落間 (px) ※改行時</label>
                            <input type="number" id="lineHeight" value="28" min="1" max="200">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>X座標 (px)</label>
                            <input type="number" id="textX" value="10" min="0" max="<?= TARGET_WIDTH ?>">
                        </div>
                        <div class="form-group">
                            <label>Y座標 (px)</label>
                            <input type="number" id="textY" value="10" min="0" max="<?= MAX_IMAGE_HEIGHT ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>配置</label>
                        <select id="textAlign">
                            <option value="left">左揃え</option>
                            <option value="center">中央揃え</option>
                            <option value="right">右揃え</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>テキスト色</label>
                        <div class="color-input-group">
                            <input type="color" id="textColorPicker" value="#ffffff">
                            <input type="text" id="textColor" value="#ffffff" placeholder="#000000">
                        </div>
                    </div>
                </div>
                
                <!-- 縁取り設定 -->
                <div class="panel">
                    <h2>🎨 縁取り設定</h2>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="outlineEnabled">
                            <label for="outlineEnabled">縁取りを有効にする</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>縁取り色</label>
                        <div class="color-input-group">
                            <input type="color" id="outlineColorPicker" value="#000000">
                            <input type="text" id="outlineColor" value="#000000" placeholder="#000000">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>縁取りの太さ (px)</label>
                        <input type="number" id="outlineWidth" value="2" min="1" max="10">
                    </div>
                </div>
                
                <!-- 出力設定 -->
                <div class="panel">
                    <h2>💾 出力設定</h2>
                    <div class="form-group">
                        <label>高さ設定</label>
                        <select id="heightMode">
                            <option value="auto">自動（テキストに合わせる）</option>
                            <option value="manual">手動指定</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>高さ (px) ※手動指定時</label>
                        <input type="number" id="manualHeight" value="500" min="1" max="<?= MAX_IMAGE_HEIGHT ?>">
                    </div>
                    <div class="form-group">
                        <label>保存ファイル名</label>
                        <input type="text" id="filename" placeholder="panel_output" value="panel_output">
                    </div>
                </div>
            </div>
            
            <!-- 右側：プレビュー -->
            <div>
                <div class="panel" style="position: sticky; top: 20px;">
                    <h2>👁️ プレビュー</h2>
                    <div class="preview-container" id="previewContainer">
                        <div class="preview-placeholder">プレビューがここに表示されます</div>
                    </div>
                    <div class="button-group">
                        <button class="btn btn-primary" onclick="updatePreview()">プレビュー更新</button>
                        <button class="btn btn-success" onclick="saveImage()">PNG保存</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // カラーピッカーとテキスト入力の同期
        document.getElementById('textColorPicker').addEventListener('input', function() {
            document.getElementById('textColor').value = this.value;
        });
        document.getElementById('textColor').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('textColorPicker').value = this.value;
            }
        });
        
        document.getElementById('outlineColorPicker').addEventListener('input', function() {
            document.getElementById('outlineColor').value = this.value;
        });
        document.getElementById('outlineColor').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('outlineColorPicker').value = this.value;
            }
        });
        
        document.getElementById('fontSize').addEventListener('input', function() {
            document.getElementById('wrapLineHeight').value = Math.round(this.value * 1.2);
            document.getElementById('lineHeight').value = Math.round(this.value * 1.4);
        });

        // ヘッダ設定トグル
        document.getElementById('headerEnabled').addEventListener('change', function() {
            document.getElementById('headerSettingsArea').style.display = this.checked ? 'block' : 'none';
        });

        // ヘッダカラー同期
        document.getElementById('headerTextColorPicker').addEventListener('input', function() {
            document.getElementById('headerTextColor').value = this.value;
        });
        document.getElementById('headerTextColor').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('headerTextColorPicker').value = this.value;
            }
        });
        document.getElementById('headerOutlineColorPicker').addEventListener('input', function() {
            document.getElementById('headerOutlineColor').value = this.value;
        });
        document.getElementById('headerOutlineColor').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('headerOutlineColorPicker').value = this.value;
            }
        });

        // ===== ブラウザ内ファイルメモリ =====
        const localFiles = {
            bgImages: {},
            headerImages: {},
            fonts: {}
        };
        let fileCounter = 1;

        function addFileToSelect(fileObj, selectId, fileMap, statusId) {
            if (!fileObj) return;
            const fileId = 'file_' + fileCounter++;
            fileMap[fileId] = fileObj;
            
            const select = document.getElementById(selectId);
            
            // 初回のプレースホルダーを消す
            if (select.options.length > 0 && select.options[0].value === '') {
                if (selectId.includes('Image')) {
                    select.options[0].text = '-- 画像を選択 --';
                }
            }
            
            const option = document.createElement('option');
            option.value = fileId;
            option.textContent = fileObj.name;
            select.appendChild(option);
            select.value = fileId;
            
            document.getElementById(statusId).textContent = '✓ 追加しました: ' + fileObj.name;
        }

        document.getElementById('imageUpload').addEventListener('change', function() {
            addFileToSelect(this.files[0], 'imageSelect', localFiles.bgImages, 'imageUploadStatus');
        });
        document.getElementById('headerImageUpload').addEventListener('change', function() {
            addFileToSelect(this.files[0], 'headerImageSelect', localFiles.headerImages, 'headerImageUploadStatus');
        });
        document.getElementById('fontUpload').addEventListener('change', function() {
            const f = this.files[0];
            addFileToSelect(f, 'fontSelect', localFiles.fonts, 'fontUploadStatus');
            addFileToSelect(f, 'headerFontSelect', localFiles.fonts, 'fontUploadStatus');
        });

        function appendFileToFormData(formData, formKey, selectId, fileMap) {
            const val = document.getElementById(selectId).value;
            if (fileMap[val]) {
                formData.append(formKey, fileMap[val]);
            }
        }

        function buildFormData(actionStr) {
            const formData = new FormData();
            formData.append('action', actionStr);
            
            // Text and standard fields
            const fields = [
                'text', 'font_size', 'line_height', 'wrap_line_height', 'text_x', 'text_y',
                'text_color', 'text_align', 'outline_color', 'outline_width', 'height_mode', 'manual_height',
                'header_text', 'header_font_size', 'header_text_align', 'header_text_color',
                'header_outline_color', 'header_outline_width'
            ];
            
            fields.forEach(f => {
                const elId = f.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
                const el = document.getElementById(elId);
                if (el) formData.append(f, el.value);
            });
            
            // Checkboxes
            formData.append('outline_enabled', document.getElementById('outlineEnabled').checked);
            formData.append('header_enabled', document.getElementById('headerEnabled').checked);
            formData.append('header_outline_enabled', document.getElementById('headerOutlineEnabled').checked);
            
            // Fonts (can be standard string or file id)
            formData.append('font', document.getElementById('fontSelect').value);
            formData.append('header_font', document.getElementById('headerFontSelect').value);
            
            // Files from memory
            appendFileToFormData(formData, 'bg_image', 'imageSelect', localFiles.bgImages);
            appendFileToFormData(formData, 'header_image', 'headerImageSelect', localFiles.headerImages);
            appendFileToFormData(formData, 'custom_font', 'fontSelect', localFiles.fonts);
            appendFileToFormData(formData, 'header_custom_font', 'headerFontSelect', localFiles.fonts);
            
            return formData;
        }

        function updatePreview() {
            const bgVal = document.getElementById('imageSelect').value;
            if (!bgVal || !localFiles.bgImages[bgVal]) {
                alert('背景画像を追加・選択してください。');
                return;
            }
            
            const formData = buildFormData('preview');
            document.getElementById('previewContainer').innerHTML = '<div class="preview-placeholder">生成中...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('previewContainer').innerHTML = 
                        '<img src="data:image/png;base64,' + data.image + '" alt="Preview">';
                } else {
                    document.getElementById('previewContainer').innerHTML = 
                        '<div class="preview-placeholder">エラー: ' + data.error + '</div>';
                }
            })
            .catch(err => {
                document.getElementById('previewContainer').innerHTML = '<div class="preview-placeholder">エラーが発生しました。</div>';
            });
        }
        
        function saveImage() {
            const bgVal = document.getElementById('imageSelect').value;
            if (!bgVal || !localFiles.bgImages[bgVal]) {
                alert('背景画像を追加・選択してください。');
                return;
            }
            
            const formData = buildFormData('save');
            formData.append('filename', document.getElementById('filename').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return res.json().then(data => {
                        alert('エラー: ' + data.error);
                    });
                }
                return res.blob().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const fn = document.getElementById('filename').value || 'panel_output';
                    a.download = fn + '.png';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                });
            })
            .catch(err => {
                console.error(err);
                alert('保存中にエラーが発生しました。');
            });
        }

        // 状態保存処理（※一時ファイルは保存できないため、テキスト等の設定値のみ）
        const SAVE_KEYS = [
            'textContent', 'fontSize', 'lineHeight', 'wrapLineHeight',
            'textX', 'textY', 'textAlign', 'textColorPicker', 'textColor', 'outlineEnabled',
            'outlineColorPicker', 'outlineColor', 'outlineWidth', 'heightMode', 'manualHeight', 'filename',
            'headerEnabled', 'headerTextContent', 'headerFontSize',
            'headerTextAlign', 'headerTextColorPicker', 'headerTextColor', 'headerOutlineEnabled',
            'headerOutlineColorPicker', 'headerOutlineColor', 'headerOutlineWidth'
        ];

        function saveFormData() {
            const data = {};
            SAVE_KEYS.forEach(key => {
                const el = document.getElementById(key);
                if (el) {
                    if (el.type === 'checkbox') {
                        data[key] = el.checked;
                    } else {
                        data[key] = el.value;
                    }
                }
            });
            localStorage.setItem('twitch_panel_settings', JSON.stringify(data));
        }

        function loadFormData() {
            const raw = localStorage.getItem('twitch_panel_settings');
            if (!raw) return;
            try {
                const data = JSON.parse(raw);
                SAVE_KEYS.forEach(key => {
                    if (data[key] !== undefined) {
                        const el = document.getElementById(key);
                        if (el) {
                            if (el.type === 'checkbox') {
                                el.checked = data[key];
                            } else {
                                el.value = data[key];
                            }
                            el.dispatchEvent(new Event('change'));
                            el.dispatchEvent(new Event('input'));
                        }
                    }
                });
            } catch (e) {
                console.error('Failed to load settings', e);
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            loadFormData();
            SAVE_KEYS.forEach(key => {
                const el = document.getElementById(key);
                if (el) {
                    el.addEventListener('change', saveFormData);
                    el.addEventListener('input', saveFormData);
                }
            });
        });
    </script>
</body>
</html>
