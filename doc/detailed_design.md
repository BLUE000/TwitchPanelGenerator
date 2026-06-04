# 詳細設計書 (Detailed Design)

## 1. フロントエンド実装詳細

### 1.1. メモリ内ファイル管理機構
サーバーへの永続的なファイルアップロードを廃止したため、ブラウザ側でファイルを管理する。
```javascript
const localFiles = {
    bgImages: {},     // { "file_1": File Object, ... }
    headerImages: {}, // { "file_2": File Object, ... }
    fonts: {}         // { "file_3": File Object, ... }
};
let fileCounter = 1;
```
- `<input type="file">` の `change` イベントで取得したファイルを上記オブジェクトに格納する。
- 同時に、対応する `<select>` 要素に `<option>` を動的に追加し、選択状態にする。

### 1.2. フォームデータの構築 (`buildFormData`)
プレビューおよび保存時、DOM要素の値と `localFiles` 内のファイル実体を統合して `FormData` を生成する。
- フォント指定について、標準フォントの場合は文字列（例："Arial"）が送信され、カスタムフォントの場合は `File` オブジェクト（バイナリデータ）が送信される。

## 2. バックエンド（PHP）実装詳細

### 2.1. フォントパス解決 (`getFontPath`)
```php
function getFontPath($font, $customFontFile = '')
```
1. **アップロードフォント**: `$customFontFile` が渡されていれば（一時ファイルが存在すれば）、そのパスを返す。
2. **ローカル探索**: サーバーの定義済みパスリスト（Windows/Linux等の各種システムパス）を探索。
3. **自動ダウンロード**: 見つからない場合、定義された `download_url` （Google Fonts等）からフォントを取得し、`DIR_FONTS` にキャッシュ保存してそのパスを返す。
4. **フォールバック**: 最終的に Arial 等のデフォルトパスを返す。

### 2.2. 画像合成ロジック (`generateImage`)
#### 1. ヘッダ処理
- `header_enabled` が `true` かつ画像が存在する場合、画像を読み込み。
- 幅を `TARGET_WIDTH` (320px) に合わせ、縦横比を維持してリサイズ（`imagecopyresampled`）。
- ヘッダテキストのバウンディングボックス (`imagettfbbox`) を計算し、指定された配置（left/center/right）に基づいてX座標を、ヘッダ高さの中央にY座標を算出。縁取りとテキストを描画。

#### 2. 本文テキストの折り返し計算
- 入力テキストを改行 (`\n`) で分割。
- 各行を1文字ずつ結合しながら `imagettfbbox` で幅を計測。
- 幅が `TARGET_WIDTH - TEXT_PADDING * 2` を超えた時点で折り返し位置と判定し、配列（段落群）を構築する独自のワードラップ処理を実装。

#### 3. 画像の高さ計算
- ヘッダの高さ ＋ 本文テキストが必要とするY座標の推移 ＋ 余白 を計算し、必要なベース高さを算出。
- 指定されたモード（auto / manual）に応じて最終的な出力解像度 (`$finalHeight`) を決定。

#### 4. 最終レンダリング
- `$finalImage` (TrueColor, アルファブレンド有効) を生成。
- ヘッダ画像を `Y=0` から描画。
- 背景画像を `Y=$headerHeight` から、画像の終端に達するまで縦方向にタイリング（`imagecopy` ループ）して描画。
- 事前に計算した座標リストに基づき、本文テキスト（および縁取り）を描画。

### 2.3. 完全インメモリ出力
テンポラリファイル（`temp_xxx.png`等）のディスク書き込みを完全に排除するため、PHPの出力バッファリングを利用する。
```php
ob_start();
imagepng($finalImage);
$imageData = ob_get_clean();
imagedestroy($finalImage);
return ['success' => true, 'data' => $imageData];
```
これにより、画像データはネットワーク送信時にのみメモリ上に存在し、レスポンス完了と共にOSによって即座に安全に破棄される。
