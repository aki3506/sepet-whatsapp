<?php
require_once 'config.php';

// Sepet verilerini session'dan al
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = [];
}

$sepet = $_SESSION['sepet'];
$toplam = 0;
$toplam_usd = 0;
$urun_sayisi = 0;
$kdv_orani = 0.20; // %20 KDV

// Sepetteki ürünlerin detaylarını çek
$sepet_urunler = [];
if (!empty($sepet)) {
    foreach ($sepet as $urun_id => $miktar) {
        $urun = $db->fetchOne("
            SELECT u.*, k.kategori_adi
            FROM urunler u
            LEFT JOIN kategoriler k ON u.kategori_id = k.kategori_id
            WHERE u.urun_id = ? AND u.aktif = 1
        ", [$urun_id]);

        if ($urun) {
            $urun['sepet_miktar'] = $miktar;
            $urun['ara_toplam'] = $urun['satis_fiyati'] * $miktar;
            $urun['ara_toplam_usd'] = $urun['doviz_satis_fiyati'] * $miktar;
            $sepet_urunler[] = $urun;
            $toplam += $urun['ara_toplam'];
            $toplam_usd += $urun['ara_toplam_usd'];
            $urun_sayisi += $miktar;
        }
    }
}

// SEPETE EKLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $urun_id = intval($_POST['urun_id'] ?? 0);
        $miktar = intval($_POST['miktar'] ?? 1);

        if ($urun_id > 0 && $miktar > 0) {
            if (isset($_SESSION['sepet'][$urun_id])) {
                $_SESSION['sepet'][$urun_id] += $miktar;
            } else {
                $_SESSION['sepet'][$urun_id] = $miktar;
            }
            echo json_encode(['success' => true, 'message' => 'Ürün sepete eklendi']);
            exit();
        }
    }

    // MİKTAR GÜNCELLE
    if ($_POST['action'] === 'update') {
        $urun_id = intval($_POST['urun_id'] ?? 0);
        $miktar = intval($_POST['miktar'] ?? 1);

        if ($urun_id > 0 && $miktar > 0) {
            $_SESSION['sepet'][$urun_id] = $miktar;
            header('Location: sepet.php');
            exit();
        }
    }

    // SEPETTEN SİL
    if ($_POST['action'] === 'remove') {
        $urun_id = intval($_POST['urun_id'] ?? 0);

        if (isset($_SESSION['sepet'][$urun_id])) {
            unset($_SESSION['sepet'][$urun_id]);
            header('Location: sepet.php');
            exit();
        }
    }

    // SEPETİ BOŞALT
    if ($_POST['action'] === 'clear') {
        $_SESSION['sepet'] = [];
        header('Location: sepet.php');
        exit();
    }
}

$whatsapp = getSetting('whatsapp_number', '905382717193');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.nkteknoloji.com.tr/logo-favicon.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://www.nkteknoloji.com.tr/logo-favicon.png">

<link rel="apple-touch-icon" sizes="180x180" href="https://www.nkteknoloji.com.tr/logo.png">
<meta property="og:image" content="https://www.nkteknoloji.com.tr/logo-kare-png.png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta property="og:type" content="website">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Sepetim - <?php echo SITE_NAME; ?></title>
    <meta name="theme-color" content="#141E30">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; min-height: 100vh; }

        /* HEADER */
        .header { background: linear-gradient(135deg, #141E30 0%, #35577D 100%); color: white; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .header-content { display: flex; align-items: center; gap: 15px; }
        .back-btn { background: rgba(255,255,255,0.1); color: white; border: none; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 20px; }
        .back-btn:active { transform: scale(0.95); background: rgba(255,255,255,0.2); }
        .header-title { flex: 1; }
        .header-title h1 { font-size: 20px; font-weight: 700; }
        .header-title p { font-size: 13px; opacity: 0.9; margin-top: 2px; }

        /* CONTAINER */
        .container { max-width: 1000px; margin: 0 auto; padding: 15px; }

        /* EMPTY CART */
        .empty-cart { text-align: center; padding: 60px 20px; background: white; border-radius: 15px; margin-top: 20px; }
        .empty-icon { font-size: 80px; margin-bottom: 20px; opacity: 0.5; }
        .empty-cart h2 { font-size: 22px; color: #333; margin-bottom: 10px; }
        .empty-cart p { color: #666; margin-bottom: 25px; }
        .btn { padding: 14px 30px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: linear-gradient(135deg, #141E30 0%, #35577D 100%); color: white; box-shadow: 0 4px 15px rgba(20,30,48,0.3); }

        /* CART ITEMS */
        .cart-items { margin-bottom: 20px; }
        .cart-item { background: white; border-radius: 15px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .item-content { display: flex; gap: 15px; }
        .item-image { width: 80px; height: 80px; border-radius: 10px; background: linear-gradient(135deg, #141E30 0%, #35577D 100%); display: flex; align-items: center; justify-content: center; font-size: 40px; flex-shrink: 0; overflow: hidden; }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-info { flex: 1; min-width: 0; }
        .item-name { font-size: 16px; font-weight: 700; color: #333; margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .item-category { font-size: 12px; color: #666; margin-bottom: 8px; }
        .item-actions { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .quantity-control { display: flex; align-items: center; gap: 8px; background: #f5f5f5; border-radius: 8px; padding: 5px; }
        .qty-btn { background: white; border: none; width: 32px; height: 32px; border-radius: 6px; font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .qty-btn:active { transform: scale(0.95); }
        .qty-input { width: 40px; text-align: center; font-size: 15px; font-weight: 700; border: none; background: transparent; }
        .remove-btn { background: #fee; color: #c33; border: none; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .remove-btn:active { background: #fdd; }

        /* SUMMARY */
        .cart-summary { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); position: sticky; top: 80px; }
        .summary-title { font-size: 18px; font-weight: 700; margin-bottom: 15px; color: #333; }
        .summary-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 15px; }
        .summary-row.total { font-size: 20px; font-weight: 800; color: #141E30; margin-top: 15px; padding-top: 15px; border-top: 2px solid #f0f0f0; }
        .checkout-btn { width: 100%; padding: 16px; background: #25D366; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px; box-shadow: 0 4px 15px rgba(37,211,102,0.3); text-decoration: none; }
        .checkout-btn:active { transform: scale(0.98); }
        .clear-btn { width: 100%; padding: 12px; background: #f5f5f5; color: #666; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .clear-btn:active { background: #eee; }

        /* DÖVİZ FİYAT GÖSTERİMİ */
        .item-price .price-dual { gap: 3px; }
        .item-price .price-usd { font-size: 16px; font-weight: 800; color: #10B981; line-height: 1.2; }
        .item-price .price-try { font-size: 14px; font-weight: 600; color: #666; line-height: 1.2; }

        .summary-row .price-dual { gap: 2px; text-align: right; }
        .summary-row .price-usd { font-size: 14px; font-weight: 700; color: #10B981; }
        .summary-row .price-try { font-size: 12px; font-weight: 600; color: #666; }

        .summary-row.total .price-dual { gap: 3px; }
        .summary-row.total .price-usd { font-size: 18px; font-weight: 800; color: #10B981; }
        .summary-row.total .price-try { font-size: 16px; font-weight: 700; color: #141E30; }

        /* TABLET+ */
        @media (min-width: 768px) {
            .container { padding: 30px 20px; display: grid; grid-template-columns: 1fr 350px; gap: 30px; align-items: start; }
            .cart-items { margin-bottom: 0; }
            .item-image { width: 100px; height: 100px; font-size: 50px; }
            .item-name { font-size: 18px; }
            .cart-summary { position: sticky; top: 100px; }
        }

        @media (min-width: 1024px) {
            .item-content:hover { background: #f9f9f9; }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-content">
            <button class="back-btn" onclick="window.location.href='index.php'">←</button>
            <div class="header-title">
                <h1>🛒 Sepetim</h1>
                <p><?php echo $urun_sayisi; ?> ürün</p>
            </div>
        </div>
    </div>

    <!-- CONTAINER -->
    <div class="container">
        <?php if (empty($sepet_urunler)): ?>
            <!-- BOŞ SEPET -->
            <div class="empty-cart" style="grid-column: 1 / -1;">
                <div class="empty-icon">🛒</div>
                <h2>Sepetiniz Boş</h2>
                <p>Alışverişe başlamak için ürünleri inceleyin!</p>
                <a href="urunler.php" class="btn btn-primary">🛍️ Ürünleri Keşfet</a>
            </div>
        <?php else: ?>
            <!-- SEPET ÜRÜNLERİ -->
            <div class="cart-items">
                <?php foreach ($sepet_urunler as $urun): ?>
                    <div class="cart-item">
                        <div class="item-content">
                            <div class="item-image">
                                <?php if (!empty($urun['ana_resim']) && file_exists(UPLOAD_PATH . 'products/' . $urun['ana_resim'])): ?>
                                    <img src="<?php echo UPLOAD_URL . 'products/' . $urun['ana_resim']; ?>" alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>">
                                <?php else: ?>
                                    🎧
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($urun['urun_adi']); ?></div>
                                <div class="item-category"><?php echo htmlspecialchars($urun['kategori_adi']); ?></div>
                                <div class="item-price"><?php echo formatPriceDual($urun['doviz_satis_fiyati'], $urun['fiyat_doviz'], false); ?></div>

                                <div class="item-actions">
                                    <form method="POST" class="quantity-control">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="urun_id" value="<?php echo $urun['urun_id']; ?>">
                                        <button type="button" class="qty-btn" onclick="updateQty(<?php echo $urun['urun_id']; ?>, -1)">−</button>
                                        <input type="number" name="miktar" value="<?php echo $urun['sepet_miktar']; ?>" min="1" max="<?php echo $urun['stok_miktari']; ?>" class="qty-input" id="qty-<?php echo $urun['urun_id']; ?>" readonly>
                                        <button type="button" class="qty-btn" onclick="updateQty(<?php echo $urun['urun_id']; ?>, 1)">+</button>
                                    </form>

                                    <form method="POST" style="margin-left: auto;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="urun_id" value="<?php echo $urun['urun_id']; ?>">
                                        <button type="submit" class="remove-btn" onclick="return confirm('Bu ürünü sepetten çıkarmak istediğinize emin misiniz?')">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SEPET ÖZET -->
            <div class="cart-summary">
                <h3 class="summary-title">Sipariş Özeti</h3>
                <div class="summary-row">
                    <span>Ürün Sayısı:</span>
                    <span><?php echo $urun_sayisi; ?> adet</span>
                </div>
                <div class="summary-row">
                    <span>Ara Toplam (KDV Hariç):</span>
                    <div style="text-align: right;">
                        <div style="font-size: 14px; font-weight: 700; color: #10B981;"><?php echo number_format($toplam_usd, 2); ?> USD + KDV</div>
                        <div style="font-size: 12px; font-weight: 600; color: #666;"><?php echo number_format(dovizToTL($toplam_usd, 'USD'), 2); ?> TL + KDV</div>
                    </div>
                </div>
                <div class="summary-row">
                    <span>KDV (%20):</span>
                    <div style="text-align: right;">
                        <?php
                        $kdv_tutari_usd = $toplam_usd * 0.20;
                        $kdv_tutari_tl = dovizToTL($kdv_tutari_usd, 'USD');
                        ?>
                        <div style="font-size: 14px; font-weight: 700; color: #10B981;"><?php echo number_format($kdv_tutari_usd, 2); ?> USD</div>
                        <div style="font-size: 12px; font-weight: 600; color: #666;"><?php echo number_format($kdv_tutari_tl, 2); ?> TL</div>
                    </div>
                </div>
                <div class="summary-row total">
                    <span>Genel Toplam (KDV Dahil):</span>
                    <div style="text-align: right;">
                        <?php
                        $genel_toplam_usd = $toplam_usd * 1.20;
                        $genel_toplam_tl = dovizToTL($genel_toplam_usd, 'USD');
                        ?>
                        <div style="font-size: 18px; font-weight: 800; color: #141E30;"><?php echo number_format($genel_toplam_usd, 2); ?> USD</div>
                        <div style="font-size: 16px; font-weight: 700; color: #141E30;"><?php echo number_format($genel_toplam_tl, 2); ?> TL</div>
                    </div>
                </div>

                <!-- WhatsApp Butonu -->
                <a href="https://wa.me/<?php echo $whatsapp; ?>?text=<?php
    $mesaj = "YENİ SİPARİŞ\n\n";
    $mesaj .= "ÜRÜNLER:\n";
    $mesaj .= "━━━━━━━━━━━━━━━━\n\n";

    foreach ($sepet_urunler as $u) {
        $urun_tl = dovizToTL($u['doviz_satis_fiyati'], 'USD');
        $ara_toplam_tl = $urun_tl * $u['sepet_miktar'];

        $mesaj .= "*" . $u['urun_adi'] . "*\n";
        $mesaj .= "Adet: " . $u['sepet_miktar'] . "\n";
        $mesaj .= "Birim Fiyat: " . number_format($u['doviz_satis_fiyati'], 2) . " USD (" . number_format($urun_tl, 2) . " TL)\n";
        $mesaj .= "Ara Toplam: " . number_format($u['ara_toplam_usd'], 2) . " USD (" . number_format($ara_toplam_tl, 2) . " TL)\n\n";
    }

    $ara_toplam_tl = dovizToTL($toplam_usd, 'USD');
    $kdv_tutari_usd = $toplam_usd * 0.20;
    $kdv_tutari_tl = dovizToTL($kdv_tutari_usd, 'USD');
    $genel_toplam_usd = $toplam_usd * 1.20;
    $genel_toplam_tl = dovizToTL($genel_toplam_usd, 'USD');

    $mesaj .= "━━━━━━━━━━━━━━━━\n";
    $mesaj .= "FİYAT DETAYI:\n\n";
    $mesaj .= "Ara Toplam (KDV Haric):\n";
    $mesaj .= "" . number_format($toplam_usd, 2) . " USD + KDV\n";
    $mesaj .= "" . number_format($ara_toplam_tl, 2) . " TL + KDV\n\n";

    $mesaj .= "KDV (%20):\n";
    $mesaj .= "" . number_format($kdv_tutari_usd, 2) . " USD\n";
    $mesaj .= "" . number_format($kdv_tutari_tl, 2) . " TL\n\n";

    $mesaj .= "━━━━━━━━━━━━━━━━\n";
    $mesaj .= "GENEL TOPLAM (KDV Dahil):\n";
    $mesaj .= "*" . number_format($genel_toplam_usd, 2) . " USD*\n";
    $mesaj .= "*" . number_format($genel_toplam_tl, 2) . " TL*\n\n";

    $mesaj .= "Siparis vermek istiyorum. Detayli bilgi alabilir miyim?";

    echo urlencode($mesaj);
?>" class="checkout-btn" target="_blank">
    💬 WhatsApp ile Sipariş Ver
</a>

                <form method="POST">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="clear-btn" onclick="return confirm('Sepeti tamamen boşaltmak istediğinize emin misiniz?')">
                        🗑️ Sepeti Boşalt
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQty(urunId, change) {
            const input = document.getElementById('qty-' + urunId);
            let newQty = parseInt(input.value) + change;

            if (newQty < 1) newQty = 1;
            if (newQty > parseInt(input.max)) newQty = parseInt(input.max);

            input.value = newQty;

            const form = input.closest('form');
            form.submit();
        }
    </script>
</body>
</html>
