<?php

Cogumelo::load("c_controller/Module");

class client extends Module
{
  public $name = "client";
  public $version = "";
  
  public $dependences = array(
   array(
     "id" =>"jquery",
     "params" => array("jquery#1.*"),
     "installer" => "bower",
     "includes" => array("jquery.js")
   ),
   array(
     "id" =>"jquery-ui",
     "params" => array("jquery-ui"),
     "installer" => "bower",
     "includes" => array("jquery-ui.js", "jquery-ui.css")
   ),
   array(
     "id" =>"less",
     "params" => array("less"),
     "installer" => "bower",
     "includes" => array()
   )
  );  

  public $includesCommon = array();
    
    
  function __construct() {
    //$this->addUrlPatterns( regex, destination );
  }

}