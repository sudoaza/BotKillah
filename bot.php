<?php

class Bots {

  function get_random_bot() {
    global $db;
    // entrada por default, comienza el spider buscando uno que esté disponible
    $siguiente = "SELECT * FROM `usuario` WHERE visto = 0 and esbot =1  and excluir = 0 limit 0,1";  
    $resultadosig = $db->sql_query($siguiente);
    $row = $db->sql_fetchrow($resultadosig);
    return $row ? new Bot($row) : null; 
  }

  function get_by_id_str($id_str) {
    global $db;
    // entrada por default, comienza el spider buscando uno que esté disponible
    $siguiente = "SELECT * FROM `usuario` WHERE id_str = '{$id_str}' limit 0,1";  
    $resultadosig = $db->sql_query($siguiente);        
    $row = $db->sql_fetchrow($resultadosig);
    return $row ? new Bot($row) : null;
  }

}

class Bot {

  public $id_str, $name, $screen_name, $location, $description, $followers_count, $friends_count, $created_at, $statuses_count, $lang;
  private $contacts = false;

/**
  Params puede ser un objeto con los datos o un id_str
*/
  function __construct($params = null) {
    global $cb, $db;
    $this->cb = $cb;
    $this->db = $db;
    if ( $params ) {
      if ( is_numeric($params) ) {
        $this->id_str = $params;
        $this->load();
      } else {
        $this->load($params);
      }
    }
  }

  function load($params = null) {
    if ($params && $params instanceof stdClass) $params = (Array) $params;

    if ($params && !$this->id_str) $this->id_str = $params['id_str'];

    if (!$params) {
      $siguiente = "SELECT * FROM `usuario` WHERE id_str = '{$this->id_str}' limit 0,1";
      $resultadosig = $this->db->sql_query($siguiente);
      $params = $this->db->sql_fetchrow($resultadosig);
    }

    if (is_array($params)) {
      $this->name = $params['name'] ? $params['name'] : $this->name;
      $this->screen_name = $params['screen_name'] ? $params['screen_name'] : $this->screen_name;
      $this->location = $params['location'] ? $params['location'] : $this->location;
      $this->description = $params['description'] ? $params['description'] : $this->description;
      $this->followers_count = $params['followers_count'] ? $params['followers_count'] : $this->followers_count;
      $this->friends_count = $params['friends_count'] ? $params['friends_count'] : $this->friends_count;
      $this->created_at = $params['created_at'] ? $params['created_at'] : $this->created_at;
      $this->statuses_count = $params['statuses_count'] ? $params['statuses_count'] : $this->statuses_count;
      $this->lang = $params['lang'] ? $params['lang'] : $this->lang;
    } else {
      // fail
    }
  }


  function get_contacts() {
    if ( ! $this->contacts ) {
      $sql = "SELECT DISTINCT u.* FROM usuario u JOIN relacion r ON (u.id_str = r.id_str_inicio OR u.id_str = r.id_str_destino) WHERE r.id_str_inicio = {$this->id_str} OR r.id_str_destino = {$this->id_str};";
      $resultadosig = $this->db->sql_query($sql);
      $contacts = Array();
      while ( $row = $this->db->sql_fetchrow($resultadosig) ) {
        $contacts[] = new Bot($row);
      }
      $this->contacts = $contacts;
    }
    return $this->contacts;
  }


/**
  Trae los contactos de twitter y los guarda en la base de datos.
  No devuelve nada! Usar get_contacts para eso
*/
  function get_and_save_contacts() {
    // Ver si traemos todos los datos y menos cantidad o solo los ids y mas cantidad
    if ( $this->friends_count > (FOLLOWERS_POR_BOT + 2) * 200 ) {
      $friends = $this->get_friends_ids();
    } else {
      $friends = $this->get_friends();
    }
    if ( $this->followers_count > (FOLLOWERS_POR_BOT + 2) * 200 ) {
      $followers = $this->get_followers_ids();
    } else {
      $followers = $this->get_followers();
    }
    $contacts = array_merge($friends,$followers);

    foreach ( $contacts as $contact ) {
      $bot_contact = new Bot($contact);
      $bot_contact->save();
      $this->add_contact($bot_contact->id_str);
    }
  }

  function get_data_if_not_done_yet() {
    if ( ! isset($this->screen_name) ) {
      $this->get_and_save_data();
    }
  }

  function get_and_save_data() {
    $parameters = array(
      'user_id'=> $this->id_str
    );

    $result = $this->cb->users_show($parameters);
    $this->load($result);
  }

  function get_friends() {
    $nextCursor = "-1";
    $i = 0;

    $parameters = array(
        'cursor' => $nextCursor,
        'count' =>200,
        'user_id'=> $this->id_str
    );

    $friends = array();

    while ( $nextCursor ) {
        $i++;
        $result = $this->cb->friends_list($parameters);
        $old_cursor = $nextCursor;
        $nextCursor = $result->next_cursor_str;

        handle_errors($result);

        if (($nextCursor == "0") or  ($nextCursor == "-1")) {$nextCursor = NULL;} // vacío

        $friends = array_merge($friends, $result->users);

        // Asi no bardea el limite
        if ( $i > FOLLOWERS_POR_BOT ) { break; }
    }

    return $friends;
  }

  function get_friends_ids() {
    $nextCursor = "-1";
    $i = 0;

    $parameters = array(
        'cursor' => $nextCursor,
        'count' =>5000,
        'user_id'=> $this->id_str
    );

    $friends = array();

    while ( $nextCursor ) {
        $i++;
        $result = $this->cb->friends_ids($parameters);
        $old_cursor = $nextCursor;
        $nextCursor = $result->next_cursor_str;

        handle_errors($result);

        if (($nextCursor == "0") or  ($nextCursor == "-1")) {$nextCursor = NULL;} // vacío

        $friends = array_merge($friends, $result->ids);

        // Asi no bardea el limite
        if ( $i > FOLLOWERS_POR_BOT ) { break; }
    }

    return $friends;
  }



  function get_followers () {
    $nextCursor = "-1";        
    $i = 0;

    $parameters = array(
        'cursor' => $nextCursor,
        'count' =>200,
        'user_id'=> $this->id_str
    );

    $followers = array();

    while ( $nextCursor ) {
        $i++;
        $result = $this->cb->followers_list($parameters);
        $old_cursor = $nextCursor;
        $nextCursor = $result->next_cursor_str;
        
        handle_errors($result);
       
        if (($nextCursor == "0") or  ($nextCursor == "-1")) {$nextCursor = NULL;} // vacío

        $followers = array_merge($followers, $result->users);

        // Asi no bardea el limite
        if ( $i > FOLLOWERS_POR_BOT ) { break; }
    }

    return $followers;
  }

  function get_followers_ids () {
    $nextCursor = "-1";        
    $i = 0;

    $parameters = array(
        'cursor' => $nextCursor,
        'count' =>5000,
        'user_id'=> $this->id_str
    );

    $followers = array();

    while ( $nextCursor ) {
        $i++;
        $result = $this->cb->followers_list($parameters);
        $old_cursor = $nextCursor;
        $nextCursor = $result->next_cursor_str;
        
        handle_errors($result);
       
        if (($nextCursor == "0") or  ($nextCursor == "-1")) {$nextCursor = NULL;} // vacío

        $followers = array_merge($followers, $result->users);

        // Asi no bardea el limite
        if ( $i > FOLLOWERS_POR_BOT ) { break; }
    }

    return $followers;
  }

  function save() {
    $existe = Bots::get_by_id_str($this->id_str);
    if ( $existe ) {
      $guarda = 'UPDATE usuario SET (';
    } else {
      $guarda = 'INSERT INTO usuario (';
    }

    $guarda .= "id_str, name, screen_name, location, description, followers_count, friends_count, created_at, statuses_count, lang)"
            . " VALUES ('$this->id_str','$this->name', '$this->screen_name', '$this->location', '$this->description', "
            . "'$this->followers_count', '$this->friends_count', '$this->created_at', '$this->statuses_count', '$this->lang')";

    $this->db->sql_query($guarda);
  }

  function add_contact($to) {
    $relacion = "INSERT INTO relacion (id_str_inicio, id_str_destino) VALUES ('{$this->id_str}','{$to}')";
    $this->db->sql_query($relacion);
  }

  function mark_as_viewed() {
    // lo marco como visto para no volver a usarlo
    $actualizado = "UPDATE usuario SET visto = 1 WHERE id_str = '{$this->id_str}'";  
    $this->db->sql_query($actualizado);
  }

  function get_and_save_tweets() {
      $max_id = $this->get_max_tweet_id();
      $i = 0;

      $parameters = array(
          'count' =>200,
          'user_id'=> $this->id_str,
          'include_rts'=> TRAER_RTS
      );

      do {
          $i++;

          if ( $max_id ) $parameters['max_id'] = $max_id;

          $result = $this->cb->statuses_userTimeline($parameters);
          
          handle_errors($result);

          foreach ( $result as $tweet ) {
            if ( isset($tweet->id_str) && isset($tweet->text) ) {
              Tweet::save_tweet($tweet);
              $max_id = $tweet->id_str;
            }
          }

          if ( $i > TWEETS_POR_BOT ) { break; }
      } while (!isset($result->errors));
  }


  // El max id del request no el id mas grande, ya se, weird...
  function get_max_tweet_id() {
    $sql = "SELECT id_str FROM tweet WHERE id_str = '{$this->id_str}' ORDER BY id_str ASC LIMIT 0,1";
    $result = $this->db->sql_query($sql);
    $row = $this->db->sql_fetchrow($result);
    $return = $row['id_str'] ? $row['id_str'] : false;
    return $return;
  }
}

function handle_errors($result) {
    if (isset($result->errors)) {
        echo '<div class="alert alert-warning"><strong>Error!</strong> '.$result->errors[0]->message."</div><br />";
    }
}


