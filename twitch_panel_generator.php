<?php
/**
 * Twitch Panel Generator
 * 1ファイル完結型 画像+テキスト合成ツール
 */

// ===== 設定定数 =====
define('APP_VERSION', '1.2.0');
define('MAX_IMAGE_HEIGHT', 1000);  // 画像の最大高さ
define('MAX_FONT_SIZE', 100);      // フォントサイズの最大値
define('TEXT_PADDING', 1);         // テキスト余白
define('TARGET_WIDTH', 320);       // Twitchパネルの横幅

$readmePath = __DIR__ . '/README.md';
$readmeContent = file_exists($readmePath) ? file_get_contents($readmePath) : 'README.md が見つかりません。';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitch Panel Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@400;700&family=Noto+Sans+JP:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #9146ff 0%, #6441a5 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 30px; }
        .panel { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 20px; }
        .panel h2 { color: #333; margin-bottom: 20px; font-size: 1.3em; border-bottom: 2px solid #9146ff; padding-bottom: 10px; margin-top: 0; }
        
        /* Accordion */
        details.panel { padding: 15px 20px; transition: all 0.3s; }
        details.panel summary { list-style: none; cursor: pointer; outline: none; margin: -15px -20px; padding: 15px 20px; border-radius: 8px; user-select: none; }
        details.panel summary::-webkit-details-marker { display: none; }
        details.panel summary h2 { display: inline-block; margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
        details.panel summary::after { content: '▼'; float: right; font-size: 16px; color: #666; margin-top: 4px; transition: transform 0.2s; }
        details[open].panel summary::after { transform: rotate(180deg); }
        details.panel .panel-content { margin-top: 20px; border-top: 2px solid #ddd; padding-top: 15px; }
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
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative; text-align: left; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-close { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #555; line-height: 1; }
        .markdown-body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif; line-height: 1.6; color: #333; }
        .markdown-body h1, .markdown-body h2 { border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; margin-bottom: 16px; margin-top: 24px; color: #111; }
        .markdown-body h1 { font-size: 2em; margin-top: 0; }
        .markdown-body p, .markdown-body ul, .markdown-body ol { margin-bottom: 16px; font-size: 15px; }
        .markdown-body ul { padding-left: 2em; }
        .markdown-body strong { font-weight: 600; }
        
        /* Footer Styles */
        .site-footer { text-align: center; margin-top: 30px; margin-bottom: 10px; color: rgba(255,255,255,0.9); font-size: 14px; }
        .site-footer a { color: white; text-decoration: underline; font-weight: 600; }
        .site-footer a:hover { color: #e0e0e0; }
        
        /* GitHub Link */
        .github-link { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.5); padding: 8px 16px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: bold; transition: background 0.3s; display: flex; align-items: center; gap: 6px; }
        .github-link:hover { background: rgba(255,255,255,0.4); color: white; text-decoration: none; }
        @media (max-width: 768px) { .github-link { position: static; display: inline-flex; margin-bottom: 15px; } }
        
        #markdownHelp code { background: #ffffff; color: #9146ff; padding: 1px 4px; border-radius: 3px; font-family: monospace; font-weight: bold; border: 1px solid #cbd5e1; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header" style="position: relative;">
            <a href="https://github.com/BLUE000/TwitchPanelGenerator/releases" target="_blank" class="github-link">
                <svg height="16" aria-hidden="true" viewBox="0 0 16 16" version="1.1" width="16" data-view-component="true" style="fill: white;">
                    <path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-5.45 7.59c-.4.08-.55-.17-.55-.38 0-.27.01-1.13.01-2.2 0-.75-.25-1.23-.54-1.48 1.78-.2 3.65-.88 3.65-3.95 0-.88-.31-1.59-.82-2.15.08-.2.36-1.02-.08-2.12 0 0-.67-.22-2.2.82-.64-.18-1.32-.27-2-.27-.68 0-1.36.09-2 .27-1.53-1.03-2.2-.82-2.2-.82-.44 1.1-.16 1.92-.08 2.12-.51.56-.82 1.28-.82 2.15 0 3.06 1.86 3.75 3.64 3.95-.23.2-.44.55-.51 1.07-.46.21-1.61.55-2.33-.66-.15-.24-.6-.83-1.23-.82-.67.01-.27.38.01.53.34.19.73.9.82 1.13.16.45.68 1.31 2.69.94 0 .67.01 1.3.01 1.49 0 .21-.15.45-.55.38A7.995 7.995 0 0 1 0 8c0-4.42 3.58-8 8-8Z"></path>
                </svg>
                プログラムをダウンロード
            </a>
            <h1 style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                🎮 Twitch Panel Generator 
                <span style="font-size: 0.4em; font-weight: normal; color: rgba(255,255,255,0.7); background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 10px; vertical-align: middle;">v<?= APP_VERSION ?></span>
            </h1>
            <p>画像にテキストを重ねてTwitchパネルを作成</p>
            <button class="btn btn-secondary" style="margin-top: 15px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.5);" onclick="showReadme()">📖 使い方を見る (README)</button>
        </div>
        
        <div class="content">
            <!-- 左側：設定パネル -->
            <div>
                
                
                <!-- 🖼️ 画像設定 -->
                <details class="panel" open>
                    <summary><h2>🖼️ 画像設定</h2></summary>
                    <div class="panel-content">
                        <!-- ヘッダ画像トグル -->
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="headerEnabled">
                                <label for="headerEnabled">ヘッダ画像を有効にする</label>
                            </div>
                        </div>
                        <div id="headerImageSettingsArea" style="display: none;">
                            <div class="form-group">
                                <label>ヘッダ画像</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <select id="headerImageSelect" style="flex: 1; margin-bottom: 0;">
                                        <option value="">-- まずは画像を追加してください --</option>
                                    </select>
                                    <input type="file" id="headerImageUpload" accept="image/*" style="display: none;">
                                    <button class="btn btn-secondary" style="white-space: nowrap; padding: 10px 15px;" onclick="document.getElementById('headerImageUpload').click()">追加</button>
                                </div>
                                <div id="headerImageUploadStatus" style="font-size: 12px; color: #666; margin-top: 5px;"></div>
                            </div>
                        </div>
                        
                        <hr style="margin: 15px 0; border: 0; border-top: 1px dashed rgba(255,255,255,0.15);">
                        
                        <!-- 背景画像設定 -->
                        <div class="form-group">
                            <label>コンテンツ画像</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <select id="imageSelect" style="flex: 1; margin-bottom: 0;">
                                    <option value="">-- まずは画像を追加してください --</option>
                                </select>
                                <input type="file" id="imageUpload" accept="image/*" style="display: none;">
                                <button class="btn btn-secondary" style="white-space: nowrap; padding: 10px 15px;" onclick="document.getElementById('imageUpload').click()">追加</button>
                            </div>
                            <div id="imageUploadStatus" style="font-size: 12px; color: #666; margin-top: 5px;"></div>
                        </div>
                    </div>
                </details>

                <!-- 📝 テキスト設定 -->
                <details class="panel">
                    <summary><h2>📝 テキスト設定</h2></summary>
                    <div class="panel-content">
                        <!-- 共通の装飾・色設定 -->
                        <div class="form-group" style="margin-bottom: 20px; border-bottom: 1px dashed rgba(255,255,255,0.15); padding-bottom: 15px;">
                            <label style="display: flex; justify-content: space-between; align-items: flex-end;">
                                <span style="font-weight: bold; color: #4CAF50;">🎨 共通マークダウン＆部分カラー設定</span>
                                <span style="font-size: 12px; color: #4CAF50; cursor: pointer;" onclick="document.getElementById('markdownHelp').style.display = document.getElementById('markdownHelp').style.display === 'none' ? 'block' : 'none'">❓マークダウンの書き方</span>
                            </label>
                            
                            <div id="markdownHelp" style="display: none; font-size: 12px; color: #2d3748; margin-bottom: 15px; background: #f7fafc; border: 1px solid #cbd5e1; border-left: 3px solid #4CAF50; padding: 8px; border-radius: 4px; line-height: 1.5;">
                                行の先頭に特定の記号をつけることで、デザインを自動で変更できます（本文のみ適用）。<br>
                                <code># テキスト</code> ： <b>見出し1</b>（文字サイズ1.5倍 ＋ 下余白）<br>
                                <code>## テキスト</code> ： <b>見出し2</b>（文字サイズ1.25倍）<br>
                                <code>- テキスト</code> ： <b>リスト</b>（先頭が「・」になり、綺麗に字下げ）<br>
                                <code>> テキスト</code> ： <b>引用</b>（左側にアクセントの縦線 ＋ 全体字下げ）<br>
                                <code>---</code> ： <b>横線</b>（幅いっぱいに区切り線を引く）<br>
                                <br>
                                太字・斜体・下線・部分文字色は文字ごとに適用できます（<b>ヘッダ・本文両対応</b>）。<br>
                                <code>**テキスト**</code> または <code>__テキスト__</code> ： <b>太字</b><br>
                                <code>*テキスト*</code> または <code>_テキスト_</code> ： <i>斜体</i><br>
                                <code>&lt;u&gt;テキスト&lt;/u&gt;</code> または <code>++テキスト++</code> ： <u>下線</u><br>
                                <br>
                                部分的な文字色変更も適用できます。<br>
                                <code>{色名:テキスト}</code> ： <b>プリセット色</b>（例： <code>{赤:重要}</code> 、 <code>{青:https://...}</code> など）<br>
                                <code>{番号:テキスト}</code> ： <b>カスタム部分色 1〜3</b>（例： <code>{1:カスタム色1の文字}</code> ）<br>
                                ※ カラー名には 赤、青、黄、緑、紫、オレンジ、ピンク、水色、白、黒 や、直接カラーコード（ <code>{#ff00aa:テキスト}</code> ）も使えます。
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group" style="margin-bottom: 5px;">
                                    <label style="font-size: 12px; margin-bottom: 2px;">カスタム部分色1 (マークダウン {1:テキスト})</label>
                                    <div class="color-input-group">
                                        <input type="color" id="customColor1Picker" value="#ff4a4a">
                                        <input type="text" id="customColor1" value="#ff4a4a" placeholder="#ff4a4a">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom: 5px;">
                                    <label style="font-size: 12px; margin-bottom: 2px;">カスタム部分色2 (マークダウン {2:テキスト})</label>
                                    <div class="color-input-group">
                                        <input type="color" id="customColor2Picker" value="#3b82f6">
                                        <input type="text" id="customColor2" value="#3b82f6" placeholder="#3b82f6">
                                    </div>
                                </div>
                            </div>
                            <div class="form-row" style="margin-top: 5px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 12px; margin-bottom: 2px;">カスタム部分色3 (マークダウン {3:テキスト})</label>
                                    <div class="color-input-group">
                                        <input type="color" id="customColor3Picker" value="#fbbf24">
                                        <input type="text" id="customColor3" value="#fbbf24" placeholder="#fbbf24">
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <!-- 空き -->
                                </div>
                            </div>
                        </div>

                        <!-- ヘッダテキスト設定（有効時のみ表示） -->
                        <!-- 🔤 表示文字設定（サブアコーディオン） -->
                        <details style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 6px; background: #ffffff; padding: 12px;" open>
                            <summary style="cursor: pointer; font-weight: bold; font-size: 15px; color: #4CAF50; outline: none; user-select: none;">
                                🔤 表示文字
                            </summary>
                            <div style="margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
                                <!-- ヘッダテキスト（ヘッダ有効時のみ表示） -->
                                <div id="headerTextInputArea" style="display: none; margin-bottom: 15px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>ヘッダテキスト</label>
                                        <input type="text" id="headerTextContent" placeholder="ヘッダテキストを入力...">
                                    </div>
                                </div>
                                <!-- 本文テキスト -->
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>本文テキスト</label>
                                    <textarea id="textContent" placeholder="ここにテキストを入力..." style="min-height: 100px;"></textarea>
                                </div>
                            </div>
                        </details>

                        <!-- ヘッダ装飾設定（サブアコーディオン、有効時のみ表示） -->
                        <details id="headerTextSettingsArea" style="display: none; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 6px; background: #ffffff; padding: 12px;">
                            <summary style="cursor: pointer; font-weight: bold; font-size: 15px; color: #4CAF50; outline: none; user-select: none;">
                                🎨 ヘッダ装飾設定
                            </summary>
                            <div style="margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
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
                                <div id="headerOutlineSettingsArea" style="display: none;">
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
                        </details>

                        <!-- 本文装飾設定（サブアコーディオン） -->
                        <details style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 6px; background: #ffffff; padding: 12px;" open>
                            <summary style="cursor: pointer; font-weight: bold; font-size: 15px; color: #4CAF50; outline: none; user-select: none;">
                                ⚙️ 本文装飾設定
                            </summary>
                            <div style="margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 10px;">
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
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="outlineEnabled">
                                        <label for="outlineEnabled">縁取りを有効にする</label>
                                    </div>
                                </div>
                                <div id="outlineSettingsArea" style="display: none;">
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
                            </div>
                        </details>
                    </div>
                </details>
                
                <!-- 出力設定 -->
                <details class="panel">
                    <summary><h2>💾 出力設定</h2></summary>
                    <div class="panel-content">
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
                    
                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                    
                    <div class="form-group">
                        <label>現在の設定をバックアップ・復元</label>
                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">※文字や色の設定を保存できます。画像は保存されないため、復元時に再度選んでください。</div>
                        <div class="button-group" style="margin-top: 0;">
                            <button class="btn btn-secondary" onclick="exportSettingsCSV()">📥 設定をCSV保存</button>
                            <button class="btn btn-secondary" onclick="document.getElementById('csvFileInput').click()">📂 設定を読込</button>
                            <input type="file" id="csvFileInput" accept=".csv" style="display: none;" onchange="importSettingsCSV(this)">
                        </div>
                    </div></div>
                </details>
            </div>
            
            <!-- 右側：プレビュー -->
            <div>
                <div class="panel" style="position: sticky; top: 20px;">
                    <h2>プレビュー</h2>
                    <div class="preview-container" id="previewContainer">
                        <div class="preview-placeholder">プレビューがここに表示されます</div>
                    </div>
                    <div class="button-group">
                        <button class="btn btn-secondary" id="toggleFrameBtn" onclick="toggleTextFrame()">枠表示: OFF</button>
                        <button class="btn btn-success" onclick="saveImage()">PNG保存</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="site-footer">
        &copy; 2026 BLUE. Version <?= APP_VERSION ?> / Released under the <a href="https://github.com/BLUE000/TwitchPanelGenerator/blob/master/LICENSE" target="_blank">MIT License</a>.
    </div>
    
    <!-- README Modal -->
    <div class="modal-overlay" id="readmeModal" onclick="closeReadme(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close" onclick="closeReadme()">&times;</span>
            <div id="readmeContent" class="markdown-body"></div>
        </div>
    </div>
    
    <script>
        // README表示ロジック
        const rawReadme = <?= json_encode($readmeContent) ?>;
        
        function showReadme() {
            document.getElementById('readmeModal').style.display = 'flex';
            document.getElementById('readmeContent').innerHTML = marked.parse(rawReadme);
        }
        
        function closeReadme(e) {
            if (!e || e.target.id === 'readmeModal') {
                document.getElementById('readmeModal').style.display = 'none';
            }
        }

        // カラーピッカーとテキスト入力の同期
        document.getElementById('textColorPicker').addEventListener('input', function() {
            document.getElementById('textColor').value = this.value;
        });
        document.getElementById('textColor').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('textColorPicker').value = this.value;
            }
        });
        
        // カスタムカラー1同期
        document.getElementById('customColor1Picker').addEventListener('input', function() {
            document.getElementById('customColor1').value = this.value;
        });
        document.getElementById('customColor1').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('customColor1Picker').value = this.value;
            }
        });
        // カスタムカラー2同期
        document.getElementById('customColor2Picker').addEventListener('input', function() {
            document.getElementById('customColor2').value = this.value;
        });
        document.getElementById('customColor2').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('customColor2Picker').value = this.value;
            }
        });
        // カスタムカラー3同期
        document.getElementById('customColor3Picker').addEventListener('input', function() {
            document.getElementById('customColor3').value = this.value;
        });
        document.getElementById('customColor3').addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                document.getElementById('customColor3Picker').value = this.value;
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
            const display = this.checked ? 'block' : 'none';
            document.getElementById('headerImageSettingsArea').style.display = display;
            document.getElementById('headerTextSettingsArea').style.display = display;
            document.getElementById('headerTextInputArea').style.display = display;
        });
        
        // 縁取り設定トグル
        document.getElementById('outlineEnabled').addEventListener('change', function() {
            document.getElementById('outlineSettingsArea').style.display = this.checked ? 'block' : 'none';
        });
        document.getElementById('headerOutlineEnabled').addEventListener('change', function() {
            document.getElementById('headerOutlineSettingsArea').style.display = this.checked ? 'block' : 'none';
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

        // ===== PHP定数のJS展開 =====
        const TEXT_PADDING = <?= TEXT_PADDING ?>;
        const TARGET_WIDTH = <?= TARGET_WIDTH ?>;
        const MAX_IMAGE_HEIGHT = <?= MAX_IMAGE_HEIGHT ?>;

        // ===== フォントファミリーのマッピング =====
        const fontMapping = {
            'Arial': "'Roboto', sans-serif",
            'Times New Roman': "'Playfair Display', serif",
            'Courier New': "'Courier Prime', monospace",
            'Verdana': "'Inter', sans-serif",
            'Meiryo': "'Noto Sans JP', sans-serif"
        };

        const customFontFamilyMap = {};
        const loadedFontFamilies = new Set();
        let bodyTextDragArea = { minY: 0, maxY: 0, active: false };

        function loadLocalFont(fileObj) {
            if (!fileObj) return Promise.resolve();
            const fontFamilyName = 'custom_' + fileObj.name.replace(/[^a-zA-Z0-9]/g, '_');
            if (loadedFontFamilies.has(fontFamilyName)) {
                return Promise.resolve(fontFamilyName);
            }
            
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const fontFace = new FontFace(fontFamilyName, e.target.result);
                    fontFace.load().then(function(loadedFace) {
                        document.fonts.add(loadedFace);
                        loadedFontFamilies.add(fontFamilyName);
                        console.log('Font loaded successfully:', fontFamilyName);
                        resolve(fontFamilyName);
                    }).catch(err => {
                        console.error('FontFace load error:', err);
                        reject(err);
                    });
                };
                reader.onerror = function(err) {
                    reject(err);
                };
                reader.readAsArrayBuffer(fileObj);
            });
        }

        // ===== カラー定数の定義 =====
        const colorMap = {
            '赤': '#ff4a4a',
            'red': '#ff4a4a',
            '青': '#3b82f6',
            'blue': '#3b82f6',
            '緑': '#10b981',
            'green': '#10b981',
            '黄': '#fbbf24',
            'yellow': '#fbbf24',
            '紫': '#9146ff',
            'purple': '#9146ff',
            '橙': '#f97316',
            'orange': '#f97316',
            'ピンク': '#ec4899',
            'pink': '#ec4899',
            '水色': '#06b6d4',
            'cyan': '#06b6d4',
            '白': '#ffffff',
            'white': '#ffffff',
            '黒': '#000000',
            'black': '#000000'
        };

        function getStyleColor(key) {
            const trimmedKey = key.trim().toLowerCase();
            if (trimmedKey === '1') return document.getElementById('customColor1').value;
            if (trimmedKey === '2') return document.getElementById('customColor2').value;
            if (trimmedKey === '3') return document.getElementById('customColor3').value;
            if (colorMap[trimmedKey]) return colorMap[trimmedKey];
            if (colorMap[key.trim()]) return colorMap[key.trim()];
            if (/^#[0-9a-fA-F]{3,8}$/.test(trimmedKey)) return key.trim();
            return key.trim();
        }

        // ===== インラインMarkdown解析ロジック =====
        function parseInlineStyles(text) {
            const chars = [];
            let isBold = false;
            let isItalic = false;
            let isUnderline = false;
            let i = 0;
            let colorStack = [];
            
            while (i < text.length) {
                const four = text.substr(i, 4);
                const three = text.substr(i, 3);
                const two = text.substr(i, 2);
                const one = text[i];
                
                // <u> タグの処理
                if (three.toLowerCase() === '<u>') {
                    isUnderline = true;
                    i += 3;
                    continue;
                } else if (four.toLowerCase() === '</u>') {
                    isUnderline = false;
                    i += 4;
                    continue;
                }
                
                // ++ 記法の処理
                if (two === '++') {
                    isUnderline = !isUnderline;
                    i += 2;
                    continue;
                }
                
                if (two === '**' || two === '__') {
                    isBold = !isBold;
                    i += 2;
                    continue;
                } else if (one === '*' || one === '_') {
                    isItalic = !isItalic;
                    i++;
                    continue;
                }
                
                if (one === '{') {
                    let closeIdx = text.indexOf('}', i);
                    if (closeIdx !== -1) {
                        let inner = text.substring(i + 1, closeIdx);
                        let colonIdx = inner.indexOf(':');
                        if (colonIdx !== -1) {
                            let colorKey = inner.substring(0, colonIdx);
                            let colorVal = getStyleColor(colorKey);
                            colorStack.push(colorVal);
                            i += 1 + colonIdx + 1;
                            continue;
                        }
                    }
                }
                
                if (one === '}' && colorStack.length > 0) {
                    colorStack.pop();
                    i++;
                    continue;
                }
                
                const curColor = colorStack.length > 0 ? colorStack[colorStack.length - 1] : null;
                chars.push({
                    char: one,
                    bold: isBold,
                    italic: isItalic,
                    underline: isUnderline,
                    color: curColor
                });
                i++;
            }
            return chars;
        }

        // ===== 画像非同期ロードヘルパー =====
        function loadImage(fileObj) {
            return new Promise((resolve, reject) => {
                if (!fileObj) {
                    resolve(null);
                    return;
                }
                const img = new Image();
                const url = URL.createObjectURL(fileObj);
                img.onload = function() {
                    URL.revokeObjectURL(url);
                    resolve(img);
                };
                img.onerror = function(err) {
                    URL.revokeObjectURL(url);
                    reject(err);
                };
                img.src = url;
            });
        }

        // ===== カラー変換ヘルパー =====
        function hexToRgba(hex, alpha) {
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        // ===== Canvas合成描画の主処理 =====
        async function drawCanvas(showFrame = false) {
            const text = document.getElementById('textContent').value;
            const fontSize = parseInt(document.getElementById('fontSize').value) || 20;
            const lineHeight = parseInt(document.getElementById('lineHeight').value) || Math.round(fontSize * 1.4);
            const wrapLineHeight = parseInt(document.getElementById('wrapLineHeight').value) || Math.round(fontSize * 1.2);
            const textX = parseInt(document.getElementById('textX').value) || 0;
            const textY = parseInt(document.getElementById('textY').value) || 0;
            const textColor = document.getElementById('textColor').value || '#ffffff';
            const textAlign = document.getElementById('textAlign').value || 'left';
            const outlineEnabled = document.getElementById('outlineEnabled').checked;
            const outlineColor = document.getElementById('outlineColor').value || '#000000';
            const outlineWidth = parseInt(document.getElementById('outlineWidth').value) || 2;
            const heightMode = document.getElementById('heightMode').value || 'auto';
            const manualHeight = parseInt(document.getElementById('manualHeight').value) || 500;
            
            // ヘッダ設定パラメータ
            const headerEnabled = document.getElementById('headerEnabled').checked;
            const headerText = document.getElementById('headerTextContent').value || '';
            const headerFontSize = parseInt(document.getElementById('headerFontSize').value) || 24;
            const headerTextAlign = document.getElementById('headerTextAlign').value || 'center';
            const headerTextColor = document.getElementById('headerTextColor').value || '#ffffff';
            const headerOutlineEnabled = document.getElementById('headerOutlineEnabled').checked;
            const headerOutlineColor = document.getElementById('headerOutlineColor').value || '#000000';
            const headerOutlineWidth = parseInt(document.getElementById('headerOutlineWidth').value) || 2;

            // フォントの特定
            const fontSelectVal = document.getElementById('fontSelect').value;
            const fontFamily = customFontFamilyMap[fontSelectVal] || fontMapping[fontSelectVal] || 'sans-serif';
            
            const headerFontSelectVal = document.getElementById('headerFontSelect').value;
            const headerFontFamily = customFontFamilyMap[headerFontSelectVal] || fontMapping[headerFontSelectVal] || 'sans-serif';

            // 画像のロード
            const bgVal = document.getElementById('imageSelect').value;
            const bgFile = localFiles.bgImages[bgVal];
            if (!bgFile) {
                throw new Error('背景画像が選択されていません。');
            }
            const bgImg = await loadImage(bgFile);
            
            let headerImg = null;
            if (headerEnabled) {
                const headerVal = document.getElementById('headerImageSelect').value;
                const headerFile = localFiles.headerImages[headerVal];
                if (headerFile) {
                    headerImg = await loadImage(headerFile);
                }
            }

            // フォント準備の完了を待機
            await document.fonts.ready;

            // サイズ計算
            const targetWidth = TARGET_WIDTH;
            const bgOrigWidth = bgImg.width;
            const bgOrigHeight = bgImg.height;
            const bgResizedHeight = Math.round(bgOrigHeight * (targetWidth / bgOrigWidth));
            
            let headerHeight = 0;
            if (headerEnabled && headerImg) {
                const hOrigWidth = headerImg.width;
                const hOrigHeight = headerImg.height;
                headerHeight = Math.round(hOrigHeight * (targetWidth / hOrigWidth));
            }

            // レイアウト・折り返し計算用の一時Canvas
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = targetWidth;
            
            let textBoundingBox = {
                minX: targetWidth,
                maxX: 0,
                minY: textY + headerHeight,
                maxY: 0
            };
            
            const lines = text.split('\n');
            const paragraphs = [];
            const availableWidth = Math.max(50, targetWidth - (textX * 2) - TEXT_PADDING * 2);
            
            for (const rawLine of lines) {
                let type = 'normal';
                let pFontSize = fontSize;
                let indent = 0;
                let isHr = false;
                
                const trimLine = rawLine.trimEnd();
                let processText = trimLine;
                
                if (/^#\s+(.*)$/u.test(trimLine)) {
                    type = 'h1';
                    pFontSize = Math.round(fontSize * 1.5);
                    processText = trimLine.match(/^#\s+(.*)$/u)[1];
                } else if (/^##\s+(.*)$/u.test(trimLine)) {
                    type = 'h2';
                    pFontSize = Math.round(fontSize * 1.25);
                    processText = trimLine.match(/^##\s+(.*)$/u)[1];
                } else if (/^[-*]\s+(.*)$/u.test(trimLine)) {
                    type = 'list';
                    pFontSize = fontSize;
                    processText = '・' + trimLine.match(/^[-*]\s+(.*)$/u)[1];
                    indent = pFontSize * 1.0;
                } else if (/^>\s+(.*)$/u.test(trimLine)) {
                    type = 'quote';
                    pFontSize = fontSize;
                    processText = trimLine.match(/^>\s+(.*)$/u)[1];
                    indent = pFontSize * 1.5;
                } else if (trimLine.trim() === '---') {
                    type = 'hr';
                    isHr = true;
                    processText = '';
                }

                // インラインスタイルのパース
                const charTokens = parseInlineStyles(processText);
                const wrappedLines = [];
                
                if (isHr) {
                    wrappedLines.push([]); // 空行
                } else {
                    let currentLine = [];
                    let currentWidth = 0;
                    
                    for (const token of charTokens) {
                        const fontStyle = token.italic ? 'italic' : '';
                        const fontWeight = token.bold ? 'bold' : '';
                        ctx.font = `${fontStyle} ${fontWeight} ${pFontSize}px ${fontFamily}`.trim().replace(/\s+/g, ' ');
                        const charWidth = ctx.measureText(token.char).width;
                        
                        const currentIndent = (type === 'quote') ? indent : ((wrappedLines.length > 0 && type === 'list') ? indent : 0);
                        const maxAvailable = availableWidth - currentIndent;
                        
                        if (currentWidth + charWidth > maxAvailable && currentLine.length > 0) {
                            wrappedLines.push(currentLine);
                            currentLine = [token];
                            currentWidth = charWidth;
                        } else {
                            currentLine.push(token);
                            currentWidth += charWidth;
                        }
                    }
                    if (currentLine.length > 0) {
                        wrappedLines.push(currentLine);
                    }
                }
                
                paragraphs.push({
                    type: type,
                    wrappedLines: wrappedLines,
                    fontSize: pFontSize,
                    indent: indent
                });
            }

            // Y座標と全高の算出
            let currentY = textY;
            const paragraphDrawData = [];
            
            for (const p of paragraphs) {
                const pFontSize = p.fontSize;
                const wrapped = p.wrappedLines;
                
                currentY += pFontSize;
                
                if (p.type === 'hr') {
                    paragraphDrawData.push({
                        type: 'hr',
                        y: currentY - Math.round(pFontSize / 2),
                        height: pFontSize
                    });
                    currentY += pFontSize;
                    continue;
                }
                
                const scale = pFontSize / fontSize;
                const pLineHeight = Math.round(lineHeight * scale);
                const pWrapLineHeight = Math.round(wrapLineHeight * scale);
                
                const linesData = [];
                const startY = currentY - pFontSize;
                
                for (let i = 0; i < wrapped.length; i++) {
                    const line = wrapped[i];
                    const lineY = currentY;
                    linesData.push({
                        tokens: line,
                        y: lineY
                    });
                    
                    if (i < wrapped.length - 1) {
                        currentY += pWrapLineHeight;
                    } else {
                        currentY += pLineHeight;
                    }
                }
                
                if (p.type === 'h1') {
                    currentY += Math.round(pFontSize * 0.5);
                }
                const endY = currentY - pLineHeight + Math.round(pFontSize * 0.3);
                
                paragraphDrawData.push({
                    type: p.type,
                    lines: linesData,
                    fontSize: pFontSize,
                    indent: p.indent,
                    startY: startY,
                    endY: endY
                });
            }

            const requiredHeight = currentY + TEXT_PADDING;
            
            // 本文テキストのドラッグ可能エリアを更新（ヘッダ高さを考慮した絶対座標）
            bodyTextDragArea.minY = textY + headerHeight;
            bodyTextDragArea.maxY = requiredHeight + headerHeight;
            bodyTextDragArea.active = (text.trim().length > 0);
            
            textBoundingBox.maxY = requiredHeight + headerHeight;
            
            let bodyHeight = 0;
            if (heightMode === 'manual' && manualHeight > 0) {
                bodyHeight = Math.min(manualHeight, MAX_IMAGE_HEIGHT);
            } else {
                bodyHeight = Math.min(Math.max(bgResizedHeight, requiredHeight), MAX_IMAGE_HEIGHT);
            }
            
            const finalHeight = bodyHeight + headerHeight;
            canvas.height = finalHeight;

            // 描画実行
            // 1. ヘッダ画像
            if (headerEnabled && headerImg) {
                ctx.drawImage(headerImg, 0, 0, targetWidth, headerHeight);
            }

            // 2. 本文背景画像タイル
            for (let y = headerHeight; y < finalHeight; y += bgResizedHeight) {
                const copyHeight = Math.min(bgResizedHeight, finalHeight - y);
                ctx.drawImage(bgImg, 0, 0, bgOrigWidth, Math.round(bgOrigHeight * (copyHeight / bgResizedHeight)), 
                              0, y, targetWidth, copyHeight);
            }

            // 3. ヘッダテキストの描画
            if (headerEnabled && headerText) {
                const hTokens = parseInlineStyles(headerText);
                
                // 全体の幅を測定
                let hTextWidth = 0;
                hTokens.forEach(t => {
                    const tBold = t.bold || headerOutlineEnabled;
                    const fontStyle = `${t.italic ? 'italic' : ''} ${tBold ? 'bold' : 'normal'} ${headerFontSize}px ${headerFontFamily}`.trim().replace(/\s+/g, ' ');
                    ctx.font = fontStyle;
                    hTextWidth += ctx.measureText(t.char).width;
                });
                
                ctx.textBaseline = 'alphabetic';
                
                let hDrawX = TEXT_PADDING;
                if (headerTextAlign === 'center') {
                    hDrawX = (targetWidth - hTextWidth) / 2;
                } else if (headerTextAlign === 'right') {
                    hDrawX = targetWidth - hTextWidth - TEXT_PADDING;
                }
                
                const hDrawY = Math.round((headerHeight + headerFontSize * 0.8) / 2);
                
                let currentX = hDrawX;
                hTokens.forEach(t => {
                    const tBold = t.bold || headerOutlineEnabled;
                    const fontStyle = `${t.italic ? 'italic' : ''} ${tBold ? 'bold' : 'normal'} ${headerFontSize}px ${headerFontFamily}`.trim().replace(/\s+/g, ' ');
                    ctx.font = fontStyle;
                    const charWidth = ctx.measureText(t.char).width;
                    
                    if (headerOutlineEnabled) {
                        ctx.lineWidth = headerOutlineWidth;
                        ctx.strokeStyle = headerOutlineColor;
                        ctx.lineJoin = 'round';
                        ctx.miterLimit = 2;
                        ctx.strokeText(t.char, currentX, hDrawY);
                    }
                    
                    ctx.fillStyle = t.color ? t.color : headerTextColor;
                    ctx.fillText(t.char, currentX, hDrawY);
                    
                    if (t.underline) {
                        ctx.beginPath();
                        ctx.strokeStyle = ctx.fillStyle;
                        ctx.lineWidth = Math.max(1, Math.round(headerFontSize / 15));
                        const lineY = hDrawY + Math.round(headerFontSize * 0.1);
                        ctx.moveTo(currentX, lineY);
                        ctx.lineTo(currentX + charWidth, lineY);
                        ctx.stroke();
                    }
                    
                    currentX += charWidth;
                });
            }

            // 4. 本文テキストの描画
            ctx.textBaseline = 'alphabetic';
            
            for (const p of paragraphDrawData) {
                if (p.type === 'hr') {
                    ctx.fillStyle = textColor;
                    ctx.fillRect(TEXT_PADDING + 10, p.y + headerHeight, targetWidth - TEXT_PADDING * 2 - 20, 2);
                    continue;
                }
                
                if (p.type === 'quote') {
                    ctx.fillStyle = hexToRgba(outlineColor, 0.4);
                    ctx.fillRect(textX + TEXT_PADDING, p.startY + headerHeight, 4, p.endY - p.startY);
                }

                for (let i = 0; i < p.lines.length; i++) {
                    const line = p.lines[i];
                    const tokens = line.tokens;
                    
                    let totalLineWidth = 0;
                    for (const token of tokens) {
                        const fontStyle = token.italic ? 'italic' : '';
                        const fontWeight = token.bold ? 'bold' : '';
                        ctx.font = `${fontStyle} ${fontWeight} ${p.fontSize}px ${fontFamily}`.trim().replace(/\s+/g, ' ');
                        totalLineWidth += ctx.measureText(token.char).width;
                    }
                    
                    const currentIndent = (p.type === 'quote') ? p.indent : ((i > 0 && p.type === 'list') ? p.indent : 0);
                    
                    let drawX = textX + TEXT_PADDING + currentIndent;
                    if (textAlign === 'center') {
                        drawX = (targetWidth - totalLineWidth) / 2 + (currentIndent / 2);
                    } else if (textAlign === 'right') {
                        drawX = targetWidth - totalLineWidth - TEXT_PADDING;
                    }

                    if (totalLineWidth > 0) {
                        if (drawX < textBoundingBox.minX) textBoundingBox.minX = drawX;
                        if (drawX + totalLineWidth > textBoundingBox.maxX) textBoundingBox.maxX = drawX + totalLineWidth;
                    }

                    for (const token of tokens) {
                        const fontStyle = token.italic ? 'italic' : '';
                        const fontWeight = token.bold ? 'bold' : '';
                        ctx.font = `${fontStyle} ${fontWeight} ${p.fontSize}px ${fontFamily}`.trim().replace(/\s+/g, ' ');
                        const charWidth = ctx.measureText(token.char).width;
                        
                        if (outlineEnabled) {
                            ctx.lineWidth = outlineWidth;
                            ctx.strokeStyle = outlineColor;
                            ctx.lineJoin = 'round';
                            ctx.miterLimit = 2;
                            ctx.strokeText(token.char, drawX, line.y + headerHeight);
                        }
                        
                        ctx.fillStyle = token.color || textColor;
                        ctx.fillText(token.char, drawX, line.y + headerHeight);
                        
                        if (token.underline) {
                            ctx.beginPath();
                            ctx.strokeStyle = ctx.fillStyle;
                            ctx.lineWidth = Math.max(1, Math.round(p.fontSize / 15));
                            const lineY = line.y + headerHeight + Math.round(p.fontSize * 0.1);
                            ctx.moveTo(drawX, lineY);
                            ctx.lineTo(drawX + charWidth, lineY);
                            ctx.stroke();
                        }
                        
                        drawX += charWidth;
                    }
                }
            }

            // 5. 枠線の描画 (showFrameが真のときのみ)
            if (showFrame && bodyTextDragArea.active && textBoundingBox.maxX > textBoundingBox.minX) {
                ctx.save();
                ctx.strokeStyle = '#9146ff'; // Twitch風の紫
                ctx.lineWidth = 1.5;
                ctx.setLineDash([4, 4]); // 点線
                
                const pad = 2; // 少し外側に余白
                const x = Math.max(0, textBoundingBox.minX - pad);
                const y = Math.max(headerHeight, textBoundingBox.minY - pad);
                const w = Math.min(targetWidth - x, (textBoundingBox.maxX - textBoundingBox.minX) + pad * 2);
                const h = Math.min(finalHeight - y, (textBoundingBox.maxY - textBoundingBox.minY) + pad * 2);
                
                ctx.strokeRect(x, y, w, h);
                ctx.restore();
            }

            return canvas;
        }

        // ===== ブラウザ内ファイルメモリ =====
        const localFiles = {
            bgImages: {},
            headerImages: {},
            fonts: {}
        };
        let fileCounter = 1;

        function addFileToSelect(fileObj, selectId, fileMap, statusId) {
            if (!fileObj) return;
            
            const select = document.getElementById(selectId);
            
            // 初回のプレースホルダーを消す
            if (select.options.length > 0 && select.options[0].value === '') {
                if (selectId.includes('Image')) {
                    select.options[0].text = '-- 画像を選択 --';
                }
            }
            
            // 重複追加を防ぐ
            let option = Array.from(select.options).find(o => o.textContent === fileObj.name);
            let fileId;
            if (!option) {
                fileId = 'file_' + fileCounter++;
                fileMap[fileId] = fileObj;
                option = document.createElement('option');
                option.value = fileId;
                option.textContent = fileObj.name;
                select.appendChild(option);
            } else {
                fileId = option.value;
                fileMap[fileId] = fileObj;
            }
            
            select.value = fileId;
            document.getElementById(statusId).textContent = '✓ 追加しました: ' + fileObj.name;
            return fileId;
        }

        document.getElementById('imageUpload').addEventListener('change', function() {
            addFileToSelect(this.files[0], 'imageSelect', localFiles.bgImages, 'imageUploadStatus');
            updatePreview();
        });
        document.getElementById('headerImageUpload').addEventListener('change', function() {
            addFileToSelect(this.files[0], 'headerImageSelect', localFiles.headerImages, 'headerImageUploadStatus');
            updatePreview();
        });
        document.getElementById('fontUpload').addEventListener('change', function() {
            const f = this.files[0];
            if (!f) return;
            const fileId = addFileToSelect(f, 'fontSelect', localFiles.fonts, 'fontUploadStatus');
            
            const headerSelect = document.getElementById('headerFontSelect');
            if (!Array.from(headerSelect.options).some(o => o.textContent === f.name)) {
                const option = document.createElement('option');
                option.value = fileId;
                option.textContent = f.name;
                headerSelect.appendChild(option);
            }
            
            loadLocalFont(f).then(fontFamilyName => {
                customFontFamilyMap[fileId] = fontFamilyName;
                updatePreview();
            }).catch(err => {
                alert('フォントのロードに失敗しました: ' + err.message);
            });
        });

        let showTextFrame = false;
        function toggleTextFrame() {
            showTextFrame = !showTextFrame;
            const btn = document.getElementById('toggleFrameBtn');
            if (btn) {
                if (showTextFrame) {
                    btn.textContent = '枠表示: ON';
                    btn.className = 'btn btn-primary';
                } else {
                    btn.textContent = '枠表示: OFF';
                    btn.className = 'btn btn-secondary';
                }
            }
            updatePreview();
        }

        function updatePreview() {
            const bgVal = document.getElementById('imageSelect').value;
            if (!bgVal || !localFiles.bgImages[bgVal]) {
                document.getElementById('previewContainer').innerHTML = '<div class="preview-placeholder">背景画像を追加・選択してください。</div>';
                return;
            }
            
            document.getElementById('previewContainer').innerHTML = '<div class="preview-placeholder">生成中...</div>';
            
            drawCanvas(showTextFrame).then(canvas => {
                const dataUrl = canvas.toDataURL('image/png');
                document.getElementById('previewContainer').innerHTML = 
                    `<img src="${dataUrl}" alt="Preview">`;
            }).catch(err => {
                console.error(err);
                document.getElementById('previewContainer').innerHTML = 
                    `<div class="preview-placeholder">エラー: ${err.message}</div>`;
            });
        }
        
        function saveImage() {
            const bgVal = document.getElementById('imageSelect').value;
            if (!bgVal || !localFiles.bgImages[bgVal]) {
                alert('背景画像を追加・選択してください。');
                return;
            }
            
            drawCanvas().then(canvas => {
                canvas.toBlob(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const fn = document.getElementById('filename').value || 'panel_output';
                    a.download = fn + '.png';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                }, 'image/png');
            }).catch(err => {
                console.error(err);
                alert('保存中にエラーが発生しました: ' + err.message);
            });
        }

        // 状態保存処理（※一時ファイルは保存できないため、テキスト等の設定値のみ）
        const SAVE_KEYS = [
            'textContent', 'fontSize', 'lineHeight', 'wrapLineHeight',
            'textX', 'textY', 'textAlign', 'textColorPicker', 'textColor',
            'customColor1Picker', 'customColor1', 'customColor2Picker', 'customColor2', 'customColor3Picker', 'customColor3',
            'outlineEnabled',
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

        function exportSettingsCSV() {
            let csvContent = "Key,Value\n";
            SAVE_KEYS.forEach(key => {
                const el = document.getElementById(key);
                if (el) {
                    let val = el.type === 'checkbox' ? el.checked : el.value;
                    let valStr = String(val);
                    // エクセルの数式誤認（-, +, =, @ から始まる文字列）を防ぐためにシングルクォートを付与
                    // 故意に入力されたシングルクォートも保護するためにエスケープ対象に含める
                    if (/^[=\+\-@']/.test(valStr)) {
                        valStr = "'" + valStr;
                    }
                    valStr = valStr.replace(/"/g, '""');
                    csvContent += `${key},"${valStr}"\n`;
                }
            });
            const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'twitch_panel_settings.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function importSettingsCSV(input) {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                let pos = 0;
                let isHeader = true;
                
                while (pos < text.length) {
                    let key = "";
                    while (pos < text.length && text[pos] !== ',') {
                        key += text[pos];
                        pos++;
                    }
                    pos++;
                    
                    let val = "";
                    if (pos < text.length && text[pos] === '"') {
                        pos++;
                        while (pos < text.length) {
                            if (text[pos] === '"') {
                                if (pos + 1 < text.length && text[pos+1] === '"') {
                                    val += '"';
                                    pos += 2;
                                } else {
                                    pos++;
                                    break;
                                }
                            } else {
                                val += text[pos];
                                pos++;
                            }
                        }
                    }
                    
                    while (pos < text.length && text[pos] !== '\n') {
                        pos++;
                    }
                    pos++;
                    
                    if (isHeader) {
                        isHeader = false;
                        continue;
                    }
                    
                    if (key.trim() !== '') {
                        // エクセル用のエスケープ（先頭のシングルクォート）を解除
                        if (/^'[=\+\-@']/.test(val)) {
                            val = val.substring(1);
                        }
                        
                        const el = document.getElementById(key.trim());
                        if (el) {
                            if (el.type === 'checkbox') {
                                el.checked = (val === 'true');
                            } else {
                                el.value = val;
                            }
                            el.dispatchEvent(new Event('change'));
                            el.dispatchEvent(new Event('input'));
                        }
                    }
                }
                alert('設定を読み込みました！');
                input.value = '';
            };
            reader.readAsText(file);
        }
        
        let debounceTimer = null;
        function debouncedUpdatePreview(delay = 150) {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = setTimeout(() => {
                updatePreview();
            }, delay);
        }
        
        // ===== プレビュー画面上での本文テキストドラッグ移動機能 =====
        let isDragging = false;
        let dragStartTextX = 0;
        let dragStartTextY = 0;
        let dragStartMouseX = 0;
        let dragStartMouseY = 0;

        function getCanvasCoords(e, container) {
            const img = container.querySelector('img');
            if (!img) return null;
            const rect = img.getBoundingClientRect();
            const scaleX = TARGET_WIDTH / rect.width;
            
            // アスペクト比から高さを逆算
            const finalHeight = (rect.height / rect.width) * TARGET_WIDTH;
            const scaleY = finalHeight / rect.height;
            
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            
            const canvasX = (clientX - rect.left) * scaleX;
            const canvasY = (clientY - rect.top) * scaleY;
            return { x: canvasX, y: canvasY };
        }

        function initDragFeature() {
            const previewContainer = document.getElementById('previewContainer');
            
            function handleDragStart(e) {
                const coords = getCanvasCoords(e, previewContainer);
                if (!coords) return;

                if (bodyTextDragArea.active && coords.y >= bodyTextDragArea.minY && coords.y <= bodyTextDragArea.maxY) {
                    isDragging = true;
                    dragStartTextX = parseInt(document.getElementById('textX').value) || 0;
                    dragStartTextY = parseInt(document.getElementById('textY').value) || 0;
                    
                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    dragStartMouseX = clientX;
                    dragStartMouseY = clientY;
                    
                    if (e.cancelable) {
                        e.preventDefault();
                    }
                }
            }

            function handleDragMove(e) {
                const img = previewContainer.querySelector('img');
                if (!img) return;

                if (isDragging) {
                    const rect = img.getBoundingClientRect();
                    const scaleX = TARGET_WIDTH / rect.width;
                    const finalHeight = (rect.height / rect.width) * TARGET_WIDTH;
                    const scaleY = finalHeight / rect.height;

                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

                    const deltaCanvasX = (clientX - dragStartMouseX) * scaleX;
                    const deltaCanvasY = (clientY - dragStartMouseY) * scaleY;

                    let newX = Math.round(dragStartTextX + deltaCanvasX);
                    let newY = Math.round(dragStartTextY + deltaCanvasY);

                    newX = Math.max(0, Math.min(newX, TARGET_WIDTH));
                    newY = Math.max(0, Math.min(newY, MAX_IMAGE_HEIGHT));

                    document.getElementById('textX').value = newX;
                    document.getElementById('textY').value = newY;

                    drawCanvas(showTextFrame).then(canvas => {
                        img.src = canvas.toDataURL('image/png');
                    }).catch(err => console.error(err));
                    
                    if (e.cancelable) {
                        e.preventDefault();
                    }
                } else {
                    const coords = getCanvasCoords(e, previewContainer);
                    if (coords && bodyTextDragArea.active && coords.y >= bodyTextDragArea.minY && coords.y <= bodyTextDragArea.maxY) {
                        img.style.cursor = 'move';
                    } else {
                        img.style.cursor = 'default';
                    }
                }
            }

            function handleDragEnd() {
                if (isDragging) {
                    isDragging = false;
                    saveFormData();
                    updatePreview();
                }
            }

            previewContainer.addEventListener('mousedown', handleDragStart);
            window.addEventListener('mousemove', handleDragMove);
            window.addEventListener('mouseup', handleDragEnd);

            previewContainer.addEventListener('touchstart', handleDragStart, { passive: false });
            window.addEventListener('touchmove', handleDragMove, { passive: false });
            window.addEventListener('touchend', handleDragEnd);
        }

        window.addEventListener('DOMContentLoaded', () => {
            loadFormData();
            SAVE_KEYS.forEach(key => {
                const el = document.getElementById(key);
                if (el) {
                    el.addEventListener('change', () => {
                        saveFormData();
                        updatePreview();
                    });
                    el.addEventListener('input', () => {
                        saveFormData();
                        debouncedUpdatePreview(150);
                    });
                }
            });
            ['imageSelect', 'headerImageSelect', 'fontSelect', 'headerFontSelect'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', updatePreview);
                }
            });
            // ドラッグ機能初期化
            initDragFeature();
            // 初期描画
            updatePreview();
        });
    </script>
</body>
</html>
