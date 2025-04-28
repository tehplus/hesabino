<?php
// اضافه کردن handler های جدید برای عملیات Ajax
add_ajax_handler('calculate_price', function() {
    $purchase_price = floatval($_POST['purchase_price'] ?? 0);
    $profit_percent = floatval($_POST['profit_percent'] ?? 0);
    $extra_costs = floatval($_POST['extra_costs'] ?? 0);

    $final_price = $purchase_price * (1 + $profit_percent/100) + $extra_costs;
    $net_profit = $final_price - $purchase_price - $extra_costs;

    return [
        'success' => true,
        'final_price' => $final_price,
        'net_profit' => $net_profit,
        'formatted_price' => number_format($final_price) . ' تومان',
        'formatted_profit' => number_format($net_profit) . ' تومان'
    ];
});

add_ajax_handler('get_price_history', function() {
    global $pdo;
    
    $product_id = intval($_GET['product_id'] ?? 0);
    $type = $_GET['type'] ?? 'all';

    $query = "SELECT * FROM price_history WHERE product_id = :product_id";
    if ($type !== 'all') {
        $query .= " AND price_type = :type";
    }
    $query .= " ORDER BY created_at DESC LIMIT 10";

    $stmt = $pdo->prepare($query);
    $params = ['product_id' => $product_id];
    if ($type !== 'all') {
        $params['type'] = $type;
    }
    
    $stmt->execute($params);
    return ['success' => true, 'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
});

// و سایر handler های مورد نیاز
?>