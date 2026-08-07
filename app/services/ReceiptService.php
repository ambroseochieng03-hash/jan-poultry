<?php

require_once
__DIR__ .
'/../../vendor/autoload.php';

require_once
__DIR__ .
'/../config/database.php';

use Mpdf\Mpdf;

use Endroid\QrCode\QrCode;

class ReceiptService
{
    private PDO $db;

    public function __construct()
    {
        $database =
        new Database();

        $this->db =
        $database->getConnection();
    }

    public function generate(
        int $orderId
    ): ?string {

        $stmt =
        $this->db->prepare(

            "

            SELECT *

            FROM orders

            WHERE id = ?

            LIMIT 1

            "

        );

        $stmt->execute([
            $orderId
        ]);

        $order =
        $stmt->fetch();

        if (!$order) {

            return null;

        }

        $itemsStmt =
        $this->db->prepare(

            "

            SELECT

                oi.quantity,
                oi.price_snapshot,

                p.name

            FROM order_items oi

            JOIN products p

                ON oi.product_id = p.id

            WHERE oi.order_id = ?

            "

        );

        $itemsStmt->execute([
            $orderId
        ]);

        $items =
        $itemsStmt->fetchAll();

        $receiptNumber =
        'JP-' .
        date('Y') .
        '-' .
        str_pad(
            (string)$orderId,
            6,
            '0',
            STR_PAD_LEFT
        );

        $rows = '';

        foreach (
            $items as $item
        ) {

            $lineTotal =
            $item['quantity']
            *
            $item['price_snapshot'];

            $rows .= '

            <tr>

                <td>' .
                htmlspecialchars(
                    $item['name']
                ) .
                '</td>

                <td>' .
                (int)$item['quantity'] .
                '</td>

                <td>KSh ' .
                number_format(
                    $item['price_snapshot'],
                    2
                ) .
                '</td>

                <td>KSh ' .
                number_format(
                    $lineTotal,
                    2
                ) .
                '</td>

            </tr>

            ';
        }

        $generatedDate =
        date(
            'd M Y H:i'
        );

        $qrText =

        "Receipt: {$receiptNumber}\n" .

        "Order: #{$orderId}\n" .

        "Customer: {$order['customer_name']}\n" .

        "Amount: KSh " .
        number_format($order['total_price'], 2) . "\n" .

        "Generated: {$generatedDate}";

        $qrCode = new QrCode($qrText);

        $qrCode->setSize(180);

        $qrImage =

        $qrCode->writeDataUri();

        $html = '

        <style>

        body{
            font-family:sans-serif;
            color:#222;
        }

        .jp-header{
            background:#07111d;
            color:white;
            padding:25px;
            border-radius:8px;
        }

        .jp-logo{
            font-size:28px;
            font-weight:bold;
            color:#d4af37;
        }

        .jp-subtitle{
            margin-top:5px;
            color:#dddddd;
        }

        .jp-section{
            margin-top:20px;
        }

        .jp-section-title{
            background:#d4af37;
            color:#07111d;
            padding:8px 12px;
            font-weight:bold;
        }
        
        .jp-receipt-info-table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        .jp-receipt-info-table td{
            vertical-align:top;
        }

        .jp-receipt-left{
            width:72%;
        }

        .jp-receipt-right{
            width:28%;
            text-align:center;
        }

        .jp-receipt-right img{
            width:150px;
            height:150px;
        }

        .jp-qr-caption{
            margin-top:8px;
            font-size:12px;
            color:#666;
        }

        .jp-table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        .jp-table th{
            background:#07111d;
            color:white;
            padding:10px;
        }

        .jp-table td{
            padding:10px;
            border:1px solid #ddd;
        }

        .jp-total{
            margin-top:20px;
            text-align:right;
            font-size:18px;
            font-weight:bold;
            color:#07111d;
        }

        .jp-barcode
        {
            margin-top: 25px;
            text-align: center;
        }

        .jp-barcode-title
        {
            font-weight: bold;
            color: #07111d;
            margin-bottom: 8px;
        }

        .jp-barcode-number
        {
            margin-top: 6px;
            color: #666;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .jp-footer{
            margin-top:40px;
            text-align:center;
            color:#666;
            font-size:12px;
        }

        </style>

        <div class="jp-header">

            <div class="jp-logo">

                JAN POULTRY

            </div>

            <div class="jp-subtitle">

                Fresh Poultry Products & Delivery

            </div>

        </div>

        <div class="jp-section">

            <div class="jp-section-title">

                RECEIPT INFORMATION

            </div>

            <table class="jp-receipt-info-table">

                <tr>

                    <td class="jp-receipt-left">

                        <p>

                            <strong>Receipt Number:</strong>
                            ' . $receiptNumber . '

                        </p>

                        <p>

                            <strong>Order Number:</strong>
                            #' . $orderId . '

                        </p>

                        <p>

                            <strong>Date Generated:</strong>
                            ' . $generatedDate . '

                        </p>

                    </td>

                    <td class="jp-receipt-right">

                        <img
                            src="' . $qrImage . '"
                            alt="QR Code">

                        <div class="jp-qr-caption">

                            Scan to verify receipt

                        </div>

                    </td>

                </tr>

            </table>

        </div>

        <div class="jp-section">

            <div class="jp-section-title">

                CUSTOMER DETAILS

            </div>

            <p>

                <strong>Name:</strong>
                ' . htmlspecialchars(
                    $order['customer_name']
                ) . '

            </p>

            <p>

                <strong>Email:</strong>
                ' . htmlspecialchars(
                    $order['customer_email']
                ) . '

            </p>

            <p>

                <strong>Phone:</strong>
                ' . htmlspecialchars(
                    $order['customer_phone']
                ) . '

            </p>

            <p>

                <strong>Delivery Address:</strong>
                ' . nl2br(
                    htmlspecialchars(
                        $order['delivery_address']
                    )
                ) . '

            </p>

        </div>

        <div class="jp-section">

            <div class="jp-section-title">

                ORDER ITEMS

            </div>

            <table class="jp-table">

                <tr>

                    <th>Product</th>

                    <th>Qty</th>

                    <th>Unit Price</th>

                    <th>Total</th>

                </tr>

                ' . $rows . '

            </table>

        </div>

        <div class="jp-total">

            Grand Total:
            KSh ' .
            number_format(
                $order['total_price'],
                2
            ) .
        '

        </div>

        <div class="jp-barcode">

            <div class="jp-barcode-title">

                Scan for Order Verification

            </div>

            <barcode
                code="' . $receiptNumber . '"
                type="C128A"
                class="barcode"
                size="1.2"
                height="1.2" />

            <div class="jp-barcode-number">

                ' . $receiptNumber . '

            </div>

        </div>

        <div class="jp-footer">

            Thank you for choosing JAN Poultry.<br>

            We appreciate your support and look forward to serving you again.

        </div>

        ';

        $tmpDir =
        __DIR__ .
        '/../../storage/mpdf';

        if (
            !is_dir($tmpDir)
        ) {

            mkdir(
                $tmpDir,
                0775,
                true
            );

        }

        $pdf =
        new Mpdf([

            'tempDir' => $tmpDir

        ]);

        $pdf->WriteHTML(
            $html
        );

        $directory =
        __DIR__ .
        '/../../public/uploads/receipts';

        if (
            !is_dir(
                $directory
            )
        ) {

            mkdir(
                $directory,
                0775,
                true
            );

        }

        $filePath =
        $directory .
        '/receipt-' .
        $orderId .
        '-' .
        time() .
        '.pdf';

        $pdf->Output(
            $filePath,
            'F'
        );

        return
        $filePath;
    }
}
