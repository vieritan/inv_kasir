<?php
// buat_retur.php - Form Buat Retur Penjualan Matching Original Screenshot
$pageTitle = "Buat Retur Penjualan";
require_once __DIR__ . '/includes/header.php';

$allInvoices  = loadData('invoices.json');
$allSales     = loadData('sales.json');
$allCustomers = loadData('customers.json');
$allItems     = loadData('items.json');

// Filter parameters for returning back to index
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : '';

$backParams = $_GET;
unset($backParams['id'], $backParams['invoice_id'], $backParams['msg']);
$backUrl = 'index.php' . (!empty($backParams) ? '?' . http_build_query($backParams) : '');

// Selected Invoice ID
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0);

// Find invoice
$currentInvoice = null;
if ($selectedId > 0) {
    foreach ($allInvoices as $inv) {
        if ((int)$inv['id'] === $selectedId) {
            $currentInvoice = $inv;
            break;
        }
    }
}

// Fallback to first invoice if available
if (!$currentInvoice && !empty($allInvoices)) {
    $currentInvoice = $allInvoices[0];
}

$alertMsg = '';

// Handle POST Save Retur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_retur') {
    $targetInvId = (int)($_POST['invoice_id'] ?? 0);
    $returQtyMap   = $_POST['retur_qty'] ?? [];
    $returBonusMap = $_POST['retur_bonus'] ?? [];

    $foundIndex = -1;
    foreach ($allInvoices as $idx => $inv) {
        if ((int)$inv['id'] === $targetInvId) {
            $foundIndex = $idx;
            break;
        }
    }

    if ($foundIndex >= 0) {
        $hasAnyRetur = false;
        $totalReturAmount = 0;
        $stockUpdates = [];

        // Helper to retrieve retur quantity for an item row flexibly
        $getVal = function($map, $idx, $itemName) {
            if (isset($map[$idx])) {
                return max(0, (int)$map[$idx]);
            }
            if (isset($map[$itemName])) {
                return max(0, (int)$map[$itemName]);
            }
            $sanitized = str_replace([' ', '.'], '_', $itemName);
            if (isset($map[$sanitized])) {
                return max(0, (int)$map[$sanitized]);
            }
            return 0;
        };

        // Update item retur quantities and calculate total retur amount
        if (isset($allInvoices[$foundIndex]['items']) && is_array($allInvoices[$foundIndex]['items'])) {
            foreach ($allInvoices[$foundIndex]['items'] as $itemIdx => &$itemRow) {
                $iName     = $itemRow['item_name'] ?? '';
                $oldRetur  = (int)($itemRow['retur'] ?? 0);
                $oldRBonus = (int)($itemRow['retur_bonus'] ?? 0);

                $rQty   = $getVal($returQtyMap, $itemIdx, $iName);
                $rBonus = $getVal($returBonusMap, $itemIdx, $iName);

                $itemRow['retur']       = $rQty;
                $itemRow['retur_bonus'] = $rBonus;

                $itemPrice = (float)($itemRow['price'] ?? 0);
                $returVal  = ($rQty * $itemPrice);
                $totalReturAmount += $returVal;

                if ($rQty > 0 || $rBonus > 0) {
                    $hasAnyRetur = true;
                }

                // Track difference for returning stock back into items.json
                $diffQty = ($rQty + $rBonus) - ($oldRetur + $oldRBonus);
                if ($diffQty != 0) {
                    $stockUpdates[$iName] = $diffQty;
                }
            }
        }

        $allInvoices[$foundIndex]['total_retur_amount'] = $totalReturAmount;
        $allInvoices[$foundIndex]['is_return']          = $hasAnyRetur ? 1 : 0;

        // Recalculate Net Total and status
        $origTotal = (float)($allInvoices[$foundIndex]['total_amount'] ?? 0);
        $netTotal  = max(0, $origTotal - $totalReturAmount);
        $paidAmt   = (float)($allInvoices[$foundIndex]['paid_amount'] ?? 0);

        if ($paidAmt >= $netTotal) {
            $allInvoices[$foundIndex]['is_cash'] = 1;
            $allInvoices[$foundIndex]['status']  = 'LUNAS';
        } else {
            $allInvoices[$foundIndex]['is_cash'] = 0;
            $allInvoices[$foundIndex]['status']  = 'BELUM LUNAS';
        }

        saveData('invoices.json', $allInvoices);

        // Update items.json stock for returned items
        if (!empty($stockUpdates)) {
            $items = loadData('items.json');
            $stockChanged = false;
            foreach ($items as &$it) {
                $iName = $it['name'] ?? '';
                if (isset($stockUpdates[$iName])) {
                    $addStock = $stockUpdates[$iName];
                    $it['stock'] = (int)($it['stock'] ?? 0) + $addStock;
                    $stockChanged = true;
                }
            }
            if ($stockChanged) {
                saveData('items.json', $items);
            }

            // Log movements
            $movements = loadData('movements.json');
            $maxMovId = 0;
            foreach ($movements as $m) {
                if (isset($m['id']) && (int)$m['id'] > $maxMovId) {
                    $maxMovId = (int)$m['id'];
                }
            }
            $invNo   = $allInvoices[$foundIndex]['invoice_no'] ?? '';
            $salesN  = $allInvoices[$foundIndex]['sales_name'] ?? '';
            $custN   = $allInvoices[$foundIndex]['customer_name'] ?? '';
            $invDate = date('Y-m-d');

            foreach ($stockUpdates as $iName => $addStock) {
                if ($addStock > 0) {
                    $maxMovId++;
                    $movements[] = [
                        'id'            => $maxMovId,
                        'movement_date' => $invDate,
                        'type'          => 'IN',
                        'item_name'     => $iName,
                        'qty'           => $addStock,
                        'ref_no'        => $invNo,
                        'sales_name'    => $salesN,
                        'customer_name' => $custN,
                        'notes'         => 'Retur Penjualan Faktur #' . $invNo
                    ];
                }
            }
            saveData('movements.json', $movements);
        }
        
        // Redirect directly to Hutang Customer page so user immediately sees the updated debt
        $redirectUrl = 'hutang_customer.php' . (!empty($backParams) ? '?' . http_build_query($backParams) : '');
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Prepare items list for rendering
$invoiceItems = [];
if ($currentInvoice && !empty($currentInvoice['items']) && is_array($currentInvoice['items'])) {
    $invoiceItems = $currentInvoice['items'];
} else {
    foreach ($allItems as $it) {
        $invoiceItems[] = [
            'item_name' => $it['name'],
            'qty' => 0,
            'bonus' => 0,
            'price' => $it['sell_price'] ?? 0,
            'retur' => 0,
            'retur_bonus' => 0
        ];
    }
}
?>

<h1 class="page-title" style="margin-bottom: 20px;">Buat Retur Penjualan</h1>

<?php if (!empty($alertMsg)): ?>
    <div style="padding: 10px 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px; font-weight: bold;">
        <?= htmlspecialchars($alertMsg) ?>
    </div>
<?php endif; ?>

<?php if ($currentInvoice): ?>
    <form method="POST" action="buat_retur.php?<?= http_build_query($_GET) ?>">
        <input type="hidden" name="action" value="save_retur">
        <input type="hidden" name="invoice_id" value="<?= $currentInvoice['id'] ?>">

        <!-- Compact Header Info Box -->
        <div style="margin-bottom: 12px; font-size: 0.95rem; line-height: 1.3;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 4px;">
                <span style="min-width: 90px; font-weight: bold; color: #333;">Tanggal</span>
                <span style="background-color: #e0eafc; color: #2b569a; padding: 2px 10px; border-radius: 10px; font-size: 0.8rem; font-weight: bold; font-family: Arial, sans-serif;">
                    <?= date('d M Y', strtotime($currentInvoice['invoice_date'])) ?>
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 4px;">
                <span style="min-width: 90px; font-weight: bold; color: #333;">No. Faktur</span>
                <span style="color: #006600; font-weight: bold; font-size: 1rem; font-family: Georgia, serif;">
                    <?= htmlspecialchars($currentInvoice['invoice_no']) ?>
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 4px;">
                <span style="min-width: 90px; font-weight: bold; color: #333;">Sales</span>
                <span style="color: #006600; font-weight: bold; font-size: 1rem; font-family: Georgia, serif;">
                    <?= htmlspecialchars(str_pad($currentInvoice['sales_id'] ?? '1', 8, '0', STR_PAD_LEFT)) ?>
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 6px;">
                <span style="min-width: 90px; font-weight: bold; color: #333;">Pembeli</span>
                <span style="color: #006600; font-weight: bold; font-size: 1rem; font-family: Georgia, serif;">
                    <?= htmlspecialchars(str_pad($currentInvoice['customer_id'] ?? '1', 8, '0', STR_PAD_LEFT)) ?>
                </span>
            </div>
        </div>

        <!-- SEMBUNYI / TAMPILKAN Toggle Buttons matching Original Screenshot -->
        <div style="margin-bottom: 15px; display: flex; gap: 10px;">
            <button type="button" id="btnSembunyi" onclick="toggleItemsVisibility(false)" class="btn" style="background: #e6f0fa; color: #1e56a0; border: 1px solid #b3d1ff; padding: 4px 16px; border-radius: 15px; font-weight: bold; font-size: 0.78rem; cursor: pointer;">SEMBUNYI</button>
            <button type="button" id="btnTampilkan" onclick="toggleItemsVisibility(true)" class="btn" style="background: #e6f0fa; color: #1e56a0; border: 1px solid #b3d1ff; padding: 4px 16px; border-radius: 15px; font-weight: bold; font-size: 0.78rem; cursor: pointer;">TAMPILKAN</button>
        </div>

        <!-- Item List Grid Matching Original Screenshot -->
        <div style="margin-bottom: 20px;">
            <div style="font-weight: bold; font-size: 1.05rem; margin-bottom: 10px; color: #333;">Item</div>
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <?php foreach ($invoiceItems as $idx => $itRow): ?>
                        <?php
                        $itemName       = $itRow['item_name'] ?? '';
                        $purchasedQty   = (int)($itRow['qty'] ?? 0);
                        $purchasedBonus = (int)($itRow['bonus'] ?? 0);
                        $returQty       = (int)($itRow['retur'] ?? 0);
                        $returBonus     = (int)($itRow['retur_bonus'] ?? 0);
                        $price          = (float)($itRow['price'] ?? 0);
                        $rowClass       = ($idx % 2 === 1) ? 'row-green' : '';
                        ?>
                        <tr class="item-row <?= $rowClass ?>" data-retur="<?= $returQty ?>">
                            <td style="padding: 8px 12px; font-weight: bold; font-size: 0.95rem; width: 250px;">
                                <?= htmlspecialchars($itemName) ?>
                                <?php if ($purchasedQty > 0 || $purchasedBonus > 0): ?>
                                    <div style="font-size: 0.8rem; color: #006600; font-weight: bold; margin-top: 2px;">
                                        (Beli: <?= $purchasedQty ?> Dus<?= $purchasedBonus > 0 ? ", Bonus: $purchasedBonus" : "" ?>)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 12px; width: 210px;">
                                <label style="margin-right: 6px; font-weight: bold;">Quantity</label>
                                <input type="number" name="retur_qty[<?= $idx ?>]" value="<?= $returQty ?>" min="0" max="<?= $purchasedQty ?>" placeholder="<?= $purchasedQty ?>" style="width: 65px; border-radius: 4px; padding: 3px 6px; border: 1px solid #767676; font-size: 0.85rem;">
                                <span style="font-size: 0.8rem; color: #555; font-weight: bold; margin-left: 4px;">/ <?= $purchasedQty ?></span>
                            </td>
                            <td style="padding: 8px 12px; width: 210px;">
                                <label style="margin-right: 6px; font-weight: bold;">Bonus</label>
                                <input type="number" name="retur_bonus[<?= $idx ?>]" value="<?= $returBonus ?>" min="0" max="<?= $purchasedBonus ?>" placeholder="<?= $purchasedBonus ?>" style="width: 65px; border-radius: 4px; padding: 3px 6px; border: 1px solid #767676; font-size: 0.85rem;">
                                <span style="font-size: 0.8rem; color: #555; font-weight: bold; margin-left: 4px;">/ <?= $purchasedBonus ?></span>
                            </td>
                            <td style="padding: 8px 12px; font-size: 0.95rem;">
                                <span style="font-weight: bold; margin-right: 8px;">Harga beli</span>
                                <?= number_format($price, 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Action Submit & Back Buttons -->
        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <button type="submit" class="btn" style="background-color: #006600; color: #ffffff; border: 1px solid #004d00; padding: 8px 20px; font-weight: bold; font-size: 0.85rem; cursor: pointer;">
                SIMPAN RETUR
            </button>
            <a href="<?= htmlspecialchars($backUrl) ?>" onclick="if (history.length > 1) { history.back(); return false; }" class="btn" style="background-color: #666666; color: #ffffff; text-decoration: none; padding: 8px 20px; font-weight: bold; font-size: 0.85rem; border-radius: 2px; display: inline-block;">
                &laquo; KEMBALI
            </a>
        </div>
    </form>

    <script>
        function toggleItemsVisibility(showAll) {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(r => {
                const returQty = parseInt(r.getAttribute('data-retur') || '0', 10);
                if (showAll) {
                    r.style.display = '';
                } else {
                    if (returQty === 0) {
                        r.style.display = 'none';
                    } else {
                        r.style.display = '';
                    }
                }
            });
        }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
