<?php
include('init.php');

if (isset($_GET["id_str"])) {$id_str = $_GET["id_str"];}

if ( ! $id_str ) {
    $usuario = Bots::get_random_bot();
    $id_str = $usuario->id_str;
}

$usuario = new Bot($id_str);

$usuario->get_data_if_not_done_yet();
$usuario->get_and_save_tweets();
$usuario->get_and_save_contacts();

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>BotKillah!<?= $usuario->screen_name ? ' - '.$usuario->screen_name : '' ?></title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/3.2.1/css/font-awesome.min.css" media="all" rel="stylesheet" type="text/css">
        <link href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/css/bootstrap.min.css" media="all" rel="stylesheet" type="text/css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js" ></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/js/bootstrap.min.js"></script>
        <script>
          $(document).ready(function(){

            var next = function() {
              if ( $('#id_str').val() != "") {
                $('#next').click();
              }
            }

            var found = $('.alert').text().search(/Rate limit/i);

            if ( found != -1 ) {
              setTimeout(function(){           
                location.reload();
              }, <?= RATE_TIMEOUT ?>);

            } else {
              next();
            }

          });
        </script>
    </head>
    <body>
 <?php
        $usuario->mark_as_viewed();

        foreach ( $usuario->get_contacts() as $f ) {
            echo  '<a href="http://twitter.com/'.$f->screen_name.'" target="_blank" >'.$f->screen_name.'</a> <a href="?id_str='.$f->id_str.'&amp;screen_name='.$f->screen_name.'"><i class="icon-chevron-sign-right"></i></a><br />';
        }

        $next = Bots::get_random_bot();
        ?>
        <form action="index.php" method="GET">

            <input id="id_str" type="hidden" name="id_str" value="<?= $next->id_str ?>">
            <input id="screen_name" type="hidden" name="screen_name" value="<?= $next->screen_name ?>">

            <input id="next" type="submit" value="Siguiente: <?= $next->screen_name ? $next->screen_name : 'NONE' ?>">

    </body>
</html>
