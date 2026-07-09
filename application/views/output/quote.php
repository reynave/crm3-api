<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Quotes <?php echo $data['name']; ?></title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link href="<?php echo base_url(); ?>assets/styles/quote.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

</head>

<body>
    <div id="invoice">

        <div class="invoice overflow-auto">
            <div style="min-width: 600px">
                <header>
                    <div class="row">
                        <div class="col">
                            <a target="_blank" href="https://lobianijs.com">
                                <img src="http://lobianijs.com/lobiadmin/version/1.0/ajax/img/logo/lobiadmin-logo-text-64.png" data-holder-rendered="true" />
                            </a>
                        </div>
                        <div class="col company-details">
                            <h2 class="name"> <?php echo $data['company']?>  </h2>
                            <div><?php echo $data['bill_street1'];?></div>
                            <div><?php echo $data['phone'];?></div>
                            <div><?php echo $data['email'];?></div>
                        </div>
                    </div>
                </header>
                <main>
                    <div class="row contacts">
                        <div class="col invoice-to">
                            <div class="text-gray-light">QUOTATION TO:</div>
                            <h2 class="to"><?php echo $data['contact']['name']?></h2> 
                            <div class="email"><a href="mailto:<?php echo $data['email']?>"><?php echo $data['email']?></a></div>
                        </div>
                        <div class="col invoice-details">
                            <h1 class="invoice-id">QUOTATION <?php echo $data['quotes_number']; ?></h1>
                            <div class="date">Date of Quotation : <?php echo date('d M Y', strtotime($data['expired_date'])) ?></div>
                            <div class="date">Due Date: <?php echo date('d M Y') ?> </div>
                        </div>
                    </div>
                    <table border="0" cellspacing="0" cellpadding="0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th class="text-left">DESCRIPTION</th>
                                <th class="text-right">PRICE</th>
                                <th class="text-right">QTY</th>
                                <th class="text-right">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($quote_item as $row){?>
                            <tr>
                                <td class="no">04</td>
                                <td class="text-left">
                                    <h3> <?php echo $row['product'];?></h3> 
                                    <div><?php echo nl2br($row['description']);?></div>
                                </td>
                                <td width="150" class="unit"><?php echo $row['currency'].' '.number_format($row['price'])?></td>
                                <td width="100" class="qty"><?php echo number_format($row['qty'])?></td>
                                <td width="150" class="total"><?php echo $row['currency'].' '.number_format($row['total'])?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"></td>
                                <td colspan="2">SUBTOTAL</td>
                                <td> <?php echo $data['currency'].' '.number_format($pricing['total'])?> </td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                                <td colspan="2">DISCOUNT  <?php echo $data['discount'];?>%</td>
                                <td> <?php echo $data['currency'].' '.number_format($pricing['discount'])?></td>
                            </tr>

                            <tr>
                                <td colspan="2"></td>
                                <td colspan="2">TAX <?php echo $data['tax'];?>%</td>
                                <td> <?php echo $data['currency'].' '.number_format($pricing['tax'])?></td>
                            </tr>


                            <tr>
                                <td colspan="2"></td>
                                <td colspan="2">SHIPPING  </td>
                                <td> <?php echo $data['currency'].' '.number_format($pricing['shipping'])?></td>
                            </tr>

                            <tr>
                                <td colspan="2"></td>
                                <td colspan="2">GRAND TOTAL</td>
                                <td> <?php echo $data['currency'].' '.number_format($pricing['grand_total'])?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="thanks">Thank you!</div>
                    <div class="notices">
                        <div>NOTICE:</div>
                        <div class="notice"><?php echo $data['term']?></div>
                    </div>
                </main>
                <footer>
                    Quotation was created on a computer and is valid without the signature and seal.
                </footer>
            </div>
            <!--DO NOT DELETE THIS div. IT is responsible for showing footer always at the bottom-->
            <div></div>
        </div>
    </div>

</body>

</html>