<?php

class Tweet {

  function get_me_pieces($string) {
    // Sacamos los simbolos de puntuacion de los numeros asi no jode c las frases
    $string = preg_replace('/(\d)([,.-]+)(\d)/m','$1$3',$string);
    
    // Separo las frases
    $pieces = preg_split('/([.,!?-]+)(\s)/m',$string);

    // Extraigo usuarios y hashtags
    $matches = array();
    preg_match_all('/[#$@]{1}[^\s:]+/m',$string,$matches);
    $pieces = array_merge($matches[0],$pieces);

    return array_unique($pieces);
  }

  function save_tweet($t) {
      global $db;

      $guarda = "INSERT INTO tweet (id_str, usuario_id_str, text, created_at) "
              . "VALUES ('{$t->id_str}','{$t->user->id_str}', '$t->text', '$t->created_at')";
      $db->sql_query($guarda);
  }

  function get_tweets_by_piece($piece) {
      global $db;
      $piece = mysql_real_escape_string($piece);
      $sql = "SELECT * FROM `tweet` WHERE text LIKE '%{$piece}%'";  
      $result = $db->sql_query($sql);        
      $return = array();
      while( $return[] = $db->sql_fetchrow($result) ) {}
      return $return;
  }

  function get_tweet_count_by_piece($piece) {
      global $db;
      $piece = mysql_real_escape_string($piece);
      $sql = "SELECT count(id_str) as cuenta FROM `tweet` WHERE text LIKE '%{$piece}%'";  
      $result = $db->sql_query($sql);        
      $return = $db->sql_fetchrow($result);
      return $return['cuenta'];
  }

  function get_all_tweets_from_bots() {
      global $db;
      $sql = "SELECT * FROM `tweet` JOIN usuario ON (usuario.esbot = 1 AND usuario.visto=1 AND tweet.usuario_id_str = usuario.id_str) WHERE tweet.visto=0";
      $result = $db->sql_query($sql);        
      $return = array();
      while( $return[] = $db->sql_fetchrow($result) ) {}
      return $return;
  }

  function mark_as_viewed($id_str) {
    global $db;
    // lo marco como visto para no volver a usarlo
    $actualizado = "UPDATE tweet SET visto = 1 WHERE id_str = '$id_str' LIMIT 1";  
    $db->sql_query($actualizado);     
  }

}
