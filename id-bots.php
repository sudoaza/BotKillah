<?php

include('init.php');

$all_tweets = Tweet::get_all_tweets_from_bots();

$best_pieces = array();

foreach ( $all_tweets as $tweet ) {
  //Tweet::mark_as_viewed($tweet['id_str']);
  foreach ( Tweet::get_me_pieces($tweet['text']) as $piece ) {
    $piece = trim($piece);
    if ( mb_strlen($piece) > 2 ) {
      $c = Tweet::get_tweet_count_by_piece($piece);
      $h = 'p_'.substr(md5(strtolower($piece)),0,6);

      // Saltear los que no se repiten
      if ( $c < 15 || isset($best_pieces[$h])) {
        continue;
      }

      $best_pieces[$h] = $piece;
    }
  }
}

?>
<form action="" method="POST" >
<?php
foreach ($best_pieces as $piece) { ?>
<input type="checkbox" name="pieces[]" value="<?= htmlspecialchars($piece) ?>" /><?= htmlspecialchars($piece) ?><br />
<?php
}
?>
<input type="submit" value="Marcar" />
</form>
