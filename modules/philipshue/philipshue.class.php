<?php
/**
* Philips Hue 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 18:08:11 [Aug 18, 2018])
*/
//
//
require_once __DIR__ . '/lib/Http.php';
require_once __DIR__ . '/lib/PhpRestClient.php';
require_once __DIR__ . '/lib/LightColors.php';
require_once __DIR__ . '/lib/AlphaHue.php';
class philipshue extends module {
/**
* philipshue
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="philipshue";
  $this->title="Philips Hue";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
 $out['API_URL']=$this->config['API_URL'];
 if (!$out['API_URL']) {
  $out['API_URL']='http://';
 }
 $out['API_KEY']=$this->config['API_KEY'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];
    $out['API_LOG_DEBMES'] = $this->config['API_LOG_DEBMES'];

 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_key;
   $this->config['API_KEY']=$api_key;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   global $api_log_debmes;
   $this->config['API_LOG_DEBMES'] = (int)$api_log_debmes;

     $this->saveConfig();
   $this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='huedevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_huedevices') {
   $this->search_huedevices($out);
  }

         if ($this->view_mode=='discover_bridge') {
             $this->discover_bridge($out);
             $this->redirect('?');
         }

     if ($this->view_mode=='discovery') {
         $this->discovery($out);
         $this->redirect('?');
     }



  if ($this->view_mode=='edit_huedevices') {
   $this->edit_huedevices($out, $this->id);
  }
  if ($this->view_mode=='delete_huedevices') {
   $this->delete_huedevices($this->id);
   $this->redirect("?data_source=huedevices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='huedevices_property') {
  if ($this->view_mode=='' || $this->view_mode=='search_huedevices_property') {
   $this->search_huedevices_property($out);
  }
  if ($this->view_mode=='edit_huedevices_property') {
   $this->edit_huedevices_property($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
    if ($this->ajax) {
        global $op;
        if ($op == 'auth') {
            $this->getConfig();

            $maxTries = 30;
            for ($i = 1; $i <= $maxTries; ++$i) {
                try {
                    $context = array('http'=>array(
                        'method'=>'POST',
                        'header'=>'Content-type: application/x-www-form-urlencoded',
                        'content'=>json_encode (array('devicetype'=>'Phue'))
                    )
                    );
                    $response_text= @file('http://'.$this->config['API_URL'].'/api/',false,stream_context_create($context))[0];

                    $response=json_decode($response_text)[0];
                    if ($response->error) {
                        if ($response->error->type!=101) {
                            echo "\n\n", "Failure to create user. Please try again! error: " . $response->error->type;
                            break;
                        }
                    }
                    else if ($response->success) {
                        $this->getConfig();
                        $this->config['API_KEY']=$response->success->username;
                        $this->saveConfig();

                        echo "\n\n", "Successfully created new user: {$response->success->username}", "\n\n";
                        exit();
                        break;
                    }
                } catch ( Exception $e) {

                    echo "\n\n", "Failure to create user. Please try again!",
                    "\n", "Reason: {$e->getMessage()}", "\n\n";

                    break;
                }

                sleep(1);
            }
            echo "\n\n", "Failure to create user. Please try again! error: " . $response->error->type;


        }
    }
 $this->admin($out);
}
/**
* huedevices search
*
* @access public*/

 function search_huedevices(&$out) {
  require(DIR_MODULES.$this->name.'/huedevices_search.inc.php');
 }

/* huedevices edit/add
*
* @access public
*/
 function edit_huedevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/huedevices_edit.inc.php');
 }
/**
* huedevices delete record
*
* @access public
*/
 function delete_huedevices($id) {
  $rec=SQLSelectOne("SELECT * FROM huedevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM huedevices WHERE ID='".$rec['ID']."'");
 }
/**
* huedevices_property search
*
* @access public
*/
 function search_huedevices_property(&$out) {
  require(DIR_MODULES.$this->name.'/huedevices_property_search.inc.php');
 }
/**
* huedevices_property edit/add
*
* @access public
*/
 function edit_huedevices_property(&$out, $id) {
  require(DIR_MODULES.$this->name.'/huedevices_property_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
     $hue = new \AlphaHue\AlphaHue($this->config["API_URL"], $this->config["API_KEY"]);

     $table='huedevices_property';
   $properties=SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
        $dev_rec_prop = SQLSelectOne("SELECT * FROM huedevices_property WHERE  ID=".(int)$properties[$i]["ID"]);
        if ($dev_rec_prop["TITLE"]=="hex")
        $hue->setLightToHex($dev_rec_prop["HUEDEVICES_ID"], $value);
        if ($dev_rec_prop["TITLE"]=="bri")
            $hue->setLightState($dev_rec_prop["HUEDEVICES_ID"], array("bri"=>round(254/100*$value)));


    }
   }
 }



    function proper_set($title,$value,$dev_id){
        $dev_rec_prop = SQLSelectOne("SELECT * FROM huedevices_property WHERE  HUEDEVICES_ID='".$dev_id."' AND TITLE='".$title."' ");
        if ($dev_rec_prop['ID']) {
            $old_value = $dev_rec_prop['VALUE'];
            $dev_rec_prop['VALUE'] =$value;
            $dev_rec_prop['UPDATED'] = date('Y-m-d H:i:s');

            SQLUpdate('huedevices_property', $dev_rec_prop);




            if ($dev_rec_prop['LINKED_OBJECT'] && $dev_rec_prop['LINKED_PROPERTY']) {
                if ($title=='bri')
                setGlobal($dev_rec_prop['LINKED_OBJECT'] . '.' . $dev_rec_prop['LINKED_PROPERTY'], round(100/254*$value), array($this->name => '0'));
           else
               setGlobal($dev_rec_prop['LINKED_OBJECT'] . '.' . $dev_rec_prop['LINKED_PROPERTY'], $value, array($this->name => '0'));
            }


            if ($dev_rec_prop['LINKED_OBJECT'] && $dev_rec_prop['LINKED_METHOD'] &&
                ($dev_rec_prop['VALUE'] != $old_value)
            ) {
                // В привязанный метод передаем через параметры "сырые" данные метрики,
                // а также общепринятые в МДМ OLD_VALUE и NEW_VALUE.
                $message_data['data']['OLD_VALUE'] = $old_value;
                $message_data['data']['NEW_VALUE'] = $value;
                callMethod($dev_rec_prop['LINKED_OBJECT'] . '.' . $dev_rec_prop['LINKED_METHOD'], $message_data['data']);
            }
        } else{
            $dev_rec_prop = array();
            $dev_rec_prop['VALUE'] = $value;
            $dev_rec_prop['TITLE'] = $title;
            $dev_rec_prop['HUEDEVICES_ID'] = $dev_id;
            $dev_rec_prop['UPDATED'] = date('Y-m-d H:i:s');
            $dev_rec_prop['ID'] = SQLInsert('huedevices_property', $dev_rec_prop);
        }

    }

    function discovery()

    {

        $this->getConfig();

        $hue = new \AlphaHue\AlphaHue($this->config["API_URL"], $this->config["API_KEY"]);
        $light_ids = $hue->getLightIds();


        foreach ($light_ids as $light_id){

            $light=$hue->getLightState($light_id);

            if (strlen($light['uniqueid'])<25) continue;
            $dev_rec = SQLSelectOne("SELECT * FROM huedevices WHERE  UUID='".$light['uniqueid']."' ");
            if ($dev_rec['ID']) {
                $dev_rec['TITLE'] = $light['name'];
                $dev_rec['UPDATED'] = date('Y-m-d H:i:s');
                SQLUpdate('huedevices', $dev_rec);
            } else{
                $dev_rec = array();
                $dev_rec['MODELID']=$light['modelid'];
                $dev_rec['UUID'] = $light['uniqueid'];
                $dev_rec['TITLE'] = $light['name'];
                $dev_rec['UPDATED'] = date('Y-m-d H:i:s');
                $dev_rec['ID'] = SQLInsert('huedevices', $dev_rec);

            }
            $this->proper_set('on',$light["state"]["on"],$dev_rec["ID"]);
            $this->proper_set('bri',$light["state"]["bri"],$dev_rec["ID"]);
            $this->proper_set('hue',$light["state"]["hue"],$dev_rec["ID"]);
            $this->proper_set('ct',$light["state"]["ct"],$dev_rec["ID"]);
           $hex=$hue->xyToRGB($light["state"]["xy"][0],$light["state"]["xy"][1],$light["state"]["bri"]);
            $this->proper_set('hex',$hex,$dev_rec["ID"]);
            $this->proper_set('xy',$light["state"]["xy"],$dev_rec["ID"]);
            $this->proper_set('sat',$light["state"]["sat"],$dev_rec["ID"]);
            if ($this->config['API_LOG_DEBMES']) {
                DebMes("******************************************LIGHT" . $light_id, 'hue');
                DebMes($light, 'hue');
            }

        }

        $listSensors = $hue->getSensors();
        foreach ($listSensors as $sensor){
            if (strlen($sensor['uniqueid'])<25) continue;
            $dev_rec = SQLSelectOne("SELECT * FROM huedevices WHERE  UUID='".$sensor['uniqueid']."' ");
            if ($dev_rec['ID']) {
                $dev_rec['TITLE'] = $sensor['name'];
                $dev_rec['UPDATED'] = date('Y-m-d H:i:s');
                SQLUpdate('huedevices', $dev_rec);
            } else{
                $dev_rec = array();
                $dev_rec['MODELID']=$sensor['modelid'];
                $dev_rec['UUID'] = $sensor['uniqueid'];
                $dev_rec['TITLE'] = $sensor['name'];
                $dev_rec['UPDATED'] = date('Y-m-d H:i:s');
                $dev_rec['ID'] = SQLInsert('huedevices', $dev_rec);
            }
            foreach ($sensor["state"] as $propKey=>$propVal)
            $this->proper_set($propKey,$propVal,$dev_rec["ID"]);

            if ($this->config['API_LOG_DEBMES']) {
                DebMes("******************************************SENSOR", 'hue');
                DebMes($sensor, 'hue');
            }


        }
    }


    /**
    Ищем бриджи
     */
    function discover_bridge($ip = '')
    {

        $this->getConfig();

        echo "Philips Hue Bridge Finder", "\n\n";
        echo "Checking meethue.com if the bridge has phoned home:", "\n";
        $response = @file_get_contents('http://www.meethue.com/api/nupnp');

        // Don't continue if bad response
        if ($response === false) {
            echo "\tRequest failed. Ensure that you have internet connection.";
            exit(1);
        }

        echo "\tRequest succeeded", "\n\n";

        // Parse the JSON response
        $bridges = json_decode($response);
        echo "Найдено ", count($bridges), "bridges \n";
// Iterate through each bridge
        foreach ($bridges as $key => $bridge) {
            echo "\tBridge #", ++$key, "\n";
            echo "\t\tID: ", $bridge->id, "\n";
            echo "\t\tInternal IP Address: ", $bridge->internalipaddress, "\n";
            echo "\t\tMAC Address: ", $bridge->macaddress, "\n";
            echo "\n";
                global $api_url;
                $this->config['API_URL']=$bridge->internalipaddress;
                $this->saveConfig();

        }
    }








    /**
     * huebridge search




/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS huedevices');
  SQLExec('DROP TABLE IF EXISTS huedevices_property');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
huedevices - 
huedevices_property - 
*/
  $data = <<<EOD
 huedevices: ID int(10) unsigned NOT NULL auto_increment
 huedevices: TITLE varchar(100) NOT NULL DEFAULT ''
 huedevices: UUID varchar(255) NOT NULL DEFAULT ''
 huedevices: MODELID varchar(255) NOT NULL DEFAULT ''
 huedevices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 huedevices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 huedevices: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 huedevices: UPDATED datetime
 huedevices_property: ID int(10) unsigned NOT NULL auto_increment
 huedevices_property: TITLE varchar(100) NOT NULL DEFAULT ''
 huedevices_property: VALUE varchar(255) NOT NULL DEFAULT ''
 huedevices_property: HUEDEVICES_ID int(10) NOT NULL DEFAULT '0'
 huedevices_property: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 huedevices_property: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 huedevices_property: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 huedevices_property: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXVnIDE4LCAyMDE4IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
