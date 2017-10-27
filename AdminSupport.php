<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once('classes/AppraisalOrderFactory.php');
require_once('daos/DAOFactory.php');
require_once ('pages/specials/baseSpecials.php');
require_once ('classes/ArchivedFiles.php');
require_once ('classes/plugins/partners/ACI/AciSkyReviewPlugin.php');
require_once ('classes/paymentHandlers/PaymentResult.php');
require_once ('classes/workflow/WorkflowActions.php');
require_once ('classes/PDFDocument/PDFDocumentTable.php');
require_once ('daos/extended/AMCProductPricingRulesDAO.php');
require_once ('classes/engines/BaseEngine.php');
require_once ('classes/MismoXML/MismoResponseParser.php');
require_once ('modules/remote/admin/users/appraiser/ManageAppraiserUser.php');
require_once ('modules/remote/admin/users/broker/ManageBrokerUser.php');
require_once ('classes/Notes.php');

class specials_AdminSupport extends specials_baseSpecials
{
    var $user;
    protected $headers = array();
    protected $csv = array();
    protected $title = "";
    protected $data = array();

    public function buildBody()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : "";
        switch ($action) {
            case "cronjobs":
                $this->cronjobs();
                break;
            case "ucdp_linking":
                $this->ucdp_linking();
                break;
            case "mass_create_appraisers":
                $this->mass_create_appraisers();
                break;
            case "mass_create_note" :
                $this->mass_create_note();
                break;
            case "remove_users":
                $this->remove_user_page();
                break;
            case "change_location_parent":
                $this->change_location_parent();
                break;
            case "product_pricing_simulate":
                $this->product_pricing_simulate();
                break;
            case "aci_sky_review":
                $this->aci_sky_review();
                break;
            case "clear_ucdp_error":
                $this->clear_ucdp_error();
                break;
            case "changes_log":
                $this->changes_log();
                break;
            case "table_data":
                $this->table_data();
                break;
            case "read_email":
                $this->read_email();
                break;
            case "appraisal_refund":
                $this->appraisal_refund();
                break;
            case "appraisal_products":
                $this->appraisal_products();
                break;
            case "move_order_to_complete":
                $this->move_order_to_complete();
                break;
            case "orders_waiting_aci":
                $this->orders_waiting_aci();
                break;
            case "change_username":
                $this->change_username();
                break;
            case "check_user_associ":
                $this->check_user_associ();
                break;
            case "workflows":
                $this->workflows();
                break;
            case "update_user_global":
                $this->update_user_global();
                break;
            case "appraisal_workflows_history":
                $this->appraisal_workflows_history();
                break;
            case "login_as_user":
                $this->login_as_user();
                break;
            case "search_table_has_column":
                $this->search_table_has_column();
                break;
            case "getNotificationObject":
                $this->getNotificationObject();
                break;
            case "generateInvoice":
                $this->generateInvoice();
                break;
            case "engine":
                $this->engine();
                break;
            default:

                break;

        }
    }

    public function mass_create_note() {
        $this->buildForm(array(
            $this->buildInput("appraisal_ids","Appraisals IDs","textarea"),
            $this->buildInput("send_from","Send From","select", $this->buildSelectOption(array(
                1 => $this->getCurrentUser()->UserName,
                2 => $this->getWebServicesUser()->UserName
            ))),
            $this->buildInput("send_to","Send To","select", $this->buildSelectOptionFromDAO(array("table" => "appraisal_note_delivery_groups"), array(9999 => "AMC or Vendor"))),
            $this->buildInput("note","Note","textarea"),
            $this->buildInput("send_notification","Send Notification","select", $this->buildSelectOption(array(
                0 => "No",
                1   => "Yes"
            )))
        ));
        $appraisal_ids = $this->getValue("appraisal_ids","");
        $send_from = $this->getValue("send_from","0");
        $send_to    = $this->getValue("send_to","0");
        $note = $this->getValue("note","");
        $send_notification = $this->getValue("send_notification",0) == 0 ? false : true;

        if($appraisal_ids!="" && $note!="") {
            $appraisal_ids = $this->splitByComaOrLine($appraisal_ids);
            $user = $send_from == 1 ? $this->getCurrentUser() : $this->getWebServicesUser();
            // group 9999
            $noteDeliveryGroups = array($send_to);

            foreach($appraisal_ids as $appraisal_id) {
                $appraisal_id = trim($appraisal_id);
                if($appraisal_id!="") {
                    if($send_to==9999) {
                        $std = new stdClass();
                        $std->APPRAISAL_ID = $appraisal_id;
                        $appraisalObj = $this->_getDAO("AppraisalsDAO")->get($std);
                        if($appraisalObj->AMC_ID > 0 && !is_null($appraisalObj->AMC_ID)) {
                            $noteDeliveryGroups = array(AppraisalNoteDeliveryGroups::AMC);
                        } elseif($appraisalObj->APPRAISER_ID > 0 && !is_null($appraisalObj->APPRAISER_ID)) {
                            $noteDeliveryGroups = array(AppraisalNoteDeliveryGroups::APPRAISER);
                        } else {
                            $noteDeliveryGroups = array(AppraisalNoteDeliveryGroups::APPRAISER, AppraisalNoteDeliveryGroups::AMC);
                        }
                    }

                    $notes = new Notes();
                    $notes->addNote($appraisal_id, $note, $user, $noteDeliveryGroups, false, $send_notification);
                    echo " {$appraisal_id} ";
                }

            }
            echo " DONE ";
        }

    }

    public function getWebServicesUser() {
        $web_service_user_id = $this->_getDAO('UsersDAO')->GetUserId('WebServiceUser');
        $User = new User();
        return $User->FetchUser($web_service_user_id);
    }

    public function splitByComaOrLine($string) {
        $string = explode(",", $string);
        if(count($string) == 1) {
            $string = explode("\n", $string[0]);
        }
        return $string;
    }

    public function _addAppraiserGEO($data) {
        $username = $this->getValue("username","",$data);
        echo "{$username} => ";
        if($username!="") {
            $user = $this->_getDAO("UsersDAO")->Execute("SELECT * FROM users where user_name=? ", array($username))->FetchObject();
            $contact_id = $user->CONTACT_ID;
            $r = array();
            $geo_type = strtolower($this->getValue("type","",$data));
            $location = $this->getValue("location","",$data);
            if (!empty($contact_id) && $geo_type!="" && $location!="") {
                $p1= "";
                switch($geo_type) {
                    case "county":
                        $t = explode(",",$location);
                        $county = trim(strtoupper($t[0]));
                        $state = trim(strtoupper($t[1]));
                        echo $contact_id." => ".$county." => $state ==>";
                        $p1 = '{"contact_id":'.$contact_id.',"data":[{"section":"geopoints","data":{"action":"add","geo_type":"county","state":"'.$state.'","county_name":"'.$county.'"}}]}';

                        break;
                }
                if($p1!="") {
                    echo " {$location} ";
                    $Appraiser = new ManageAppraiserUser();
                    $x = $this->jsonResult($Appraiser->saveData($p1));
                }

            }

        } else {
            echo "NOT FOUND";
        }
        echo "<br>";

    }

    public function mass_create_appraisers()
    {
        $this->buildForm(array(

            $this->buildInput("username", "Username", "text"),
            $this->buildInput("first_name", "First Name", "text"),
            $this->buildInput("last_name", "Last Name", "text"),
            $this->buildInput("email", "Email", "text"),
            $this->buildInput("company_name", "Company Name", "text"),
            $this->buildInput("address", "Address", "text"),
            $this->buildInput("city", "City", "text"),
            $this->buildInput("state", "State", "text"),
            $this->buildInput("zipcode", "Zipcode", "text"),
            $this->buildInput("office_numer", "Office Number", "text"),
            $this->buildInput("cell_number", "Cell Number", "text"),

            // license
            $this->buildInput("fha", "FHA (true|false)", "select", $this->buildSelectOption(array("t" => "t", "f" => "f"))),
            $this->buildInput("license_level", "License Level", "select", $this->buildSelectOption(array(1 => "Licensed Residential", 2 => "Certified Residential", 3 => "Certified General"))),
            $this->buildInput("license_exp", "License Exp", "text"),
            $this->buildInput("license_state", "License State", "text"),
            $this->buildInput("license_number", "License Number", "text"),
            // insurance
            $this->buildInput("insurance_carrier", "Insurance Carrier", "text"),
            $this->buildInput("insurance_policy", "Insurance Policy", "text"),
            $this->buildInput("insurance_exp", "Insurance Exp", "text"),
            $this->buildInput("insurance_limit_total", "insurance_limit_total", "text"),
            // assignment
            $this->buildInput("monthly_maximum", "Monthly Maximum", "text"),
            $this->buildInput("assignment_threshold", "Assignment Threshold", "text"),
            $this->buildInput("enable_manual_assignment", "Enable Manual Assignment", "text"),
            $this->buildInput("mass_file", "CSV Mass Appraisers Data", "file"),
            $this->buildInput("mass_geo_file", "CSV Mass GEO Data", "file"),
        ));

        $mass_geo_file = isset($_FILES['mass_geo_file']) ? $_FILES['mass_geo_file'] : null;
        $path = "/var/www/tandem.inhouse-solutions.com/scripts";
        $safeGeo = $path."/_geo.csv";
        if(file_exists($safeGeo)) {
            $mass_geo_file['tmp_name'] = $safeGeo;
        }
        if (!empty($mass_geo_file) && $mass_geo_file['tmp_name']!="") {
            $Users = array();
            $csv = new CSVFile($mass_geo_file['tmp_name']);

            echo "Starting GEO Data";
            foreach ($csv as $user) {
                $tmp_user = array();
                if (!is_array($user)) {
                    continue;
                }

                foreach ($user as $key => $value) {
                    $key = strtolower(str_replace(' ', '_', trim($key)));
                    if (trim($key) == "") {
                        continue;
                    }
                    if (in_array($key, array("sites", "add_to_sites"))) {
                        $key = "site";
                    }
                    if ($key == "email_address") {
                        $key = "email";
                    }
                    if ($key == "user_name") {
                        $key = "username";
                    }
                    if ($key == "party") {
                        $key = "parties";
                    }
                    if ($value === "t" || strtolower($value) === "true") {
                        $value = true;
                    }
                    if ($value === "f" || strtolower($value) === "false") {
                        $value = false;
                    }
                    $tmp_user[$key] = $value;
                }
                if (!isset($tmp_user['username'])) {
                    $tmp_user['username'] = strtolower($tmp_user['email']);
                }
                $Users[] = $tmp_user;

            }
            @unlink($mass_geo_file['tmp_name']);

            foreach($Users as $User) {
                if(!empty($User['username'])) {
                    $User['username'] = strtolower($User['username']);
                    $this->_addAppraiserGEO($User);
                }
            }
            echo "DONE";
            exit;
        }

        $file_upload = isset($_FILES['mass_file']) ? $_FILES['mass_file'] : null;
        $Users = array();

        $safeAppraiser = $path."/_appraisers.csv";
        if(file_exists($safeAppraiser)) {
            $file_upload['tmp_name'] = $safeAppraiser;
        }

        if (!empty($file_upload) && $file_upload['tmp_name']!="") {
            $csv = new CSVFile($file_upload['tmp_name']);
            foreach ($csv as $user) {
                $tmp_user = array();
                if (!is_array($user)) {
                    continue;
                }
                foreach ($user as $key => $value) {
                    $key = strtolower(str_replace(' ', '_', trim($key)));
                    if (trim($key) == "") {
                        continue;
                    }
                    if (in_array($key, array("sites", "add_to_sites"))) {
                        $key = "site";
                    }
                    if ($key == "email_address") {
                        $key = "email";
                    }
                    if ($key == "user_name") {
                        $key = "username";
                    }
                    if ($key == "party") {
                        $key = "parties";
                    }
                    if ($value === "t" || strtolower($value) === "true") {
                        $value = true;
                    }
                    if ($value === "f" || strtolower($value) === "false") {
                        $value = false;
                    }
                    $tmp_user[$key] = $value;
                }
                if (!isset($tmp_user['username'])) {
                    $tmp_user['username'] = strtolower($tmp_user['email']);
                }
                $Users[] = $tmp_user;
            }
            @unlink($file_upload['tmp_name']);
        } // end file upload


        if(empty($Users)) {
            $Users[] = $_REQUEST;
        }

        foreach ($Users as $user) {
            if (!empty($user['username'])) {
                $user['username'] = strtolower($user['username']);
                $this->_updateAppraiserInfo($user);
            }
        }


    }


    public function _updateAppraiserInfo($data) {
        $username = $this->getValue("username","",$data);
        echo "{$username} => ";
        if($username!="") {

            $user = $this->_getDAO("UsersDAO")->Execute("SELECT * FROM users where user_name=? ",array($username))->FetchObject();
            $r = array();
            $r['fha'] = $this->getValue("fha","",$data);
            $r['license_state'] = $this->getValue("license_state","",$data);
            $r['license_level'] = $this->getValue("license_level","",$data);
            $r['license_exp'] = $this->getValue("license_exp","",$data);
            $r['license_number'] = $this->getValue("license_number","",$data);

            $r['insurance_carrier'] = $this->getValue("insurance_carrier","",$data);
            $r['insurance_policy'] = $this->getValue("insurance_policy","",$data);
            $r['insurance_exp'] = $this->getValue("insurance_exp","",$data);
            $r['insurance_limit_total'] = $this->getValue("insurance_limit_total","",$data);

            $r['monthly_maximum'] = $this->getValue("monthly_maximum","",$data);
            $r['assignment_threshold'] = $this->getValue("assignment_threshold","",$data);
            $r['enable_manual_assignment'] = $this->getValue("enable_manual_assignment","",$data);

            $r['first_name'] = $this->getValue("first_name","",$data);
            $r['last_name'] = $this->getValue("last_name","",$data);
            $r['contact_email'] = $this->getValue("email","",$data);

            $r['company_name'] = $this->getValue("company_name","",$data);
            $r['address'] = $this->getValue("address","",$data);
            $r['city'] = $this->getValue("city","",$data);
            $r['state'] = $this->getValue("state","",$data);
            $r['zipcode'] = $this->getValue("zipcode","",$data);
            $r['office_phone'] = $this->getValue("office_phone","",$data);
            $r['cell_phone'] = $this->getValue("cell_phone","",$data);

            $r['class'] = $this->getValue("class","", $data);
            $r['locations'] = $this->getValue("locations","", $data);
            echo $r['class']." => ";
            if(!empty($user->USER_ID) && !empty($user->CONTACT_ID) && $r['class'] != "") {
                $contact_id = $user->CONTACT_ID;
                $user_id = $user->USER_ID;

                // $license
                $fha = $this->getTrueAsString($this->isTrue($r['fha']));

                $license_state = $r['license_state'];
                $license_level = $r['license_level'];
                if(!is_numeric($license_level)) {
                    $license_level = strtolower($license_level);
                    switch($license_level) {
                        case "licensed residential":
                            $license_level = 1;
                            break;
                        case "certified residential":
                            $license_level = 2;
                            break;
                        case "certified general":
                            $license_level = 3;
                            break;
                    }
                }
                $license_exp = $r['license_exp'];
                if($license_exp != "") {
                    $license_exp =  @date("Y-m-d", strtotime($license_exp));
                }
                $license_number = $r['license_number'];






                $class = "Manage".$r['class'];
                $Appraiser = new $class();

                if($license_number!="") {
                    $p1 = '{"contact_id":'.$contact_id.',
                        "data":[
                            {"section":"licenses",
                                    "data":{"action":"add",
                                                    "state":"'.$license_state.'",
                                                    "fha_approved_flag":'.$fha.',
                                                    "appraiser_license_types_id":"'.$license_level.'",
                                                    "license_number":"'.$license_number.'",
                                                    "license_issue_dt":"",
                                                    "license_exp_dt":"'.$license_exp.'"}
                               }                                                                         
                            ]
                        }';
                    $this->jsonResult($Appraiser->saveData($p1));
                }



                // insurance
                $insurance_carrier = $r['insurance_carrier'];
                $insurance_policy = $r['insurance_policy'];
                $insurance_exp = $r['insurance_exp'];
                $insurance_limit_total = $r['insurance_limit_total'];
                if($insurance_exp != "" ) {
                    $insurance_exp = @date("Y-m-d", strtotime($insurance_exp));
                }

                if($insurance_carrier!="") {
                    $p1 = '{"contact_id":'.$contact_id.',"data":[{"section":"insurance",
                                    "data":{"insurance_carrier":"'.$insurance_carrier.'"}
                              }]}';
                    $this->jsonResult($Appraiser->saveData($p1));
                }

                if($insurance_policy!="") {
                    $p1 = '{"contact_id":'.$contact_id.',"data":[{"section":"insurance",
                                    "data":{"insurance_policy":"'.$insurance_policy.'"}
                              }]}';
                    $this->jsonResult($Appraiser->saveData($p1));
                }

                if($insurance_limit_total!="") {
                    $p1 = '{"contact_id":'.$contact_id.',"data":[{"section":"insurance",
                                    "data":{"insurance_limit_total":"'.$insurance_limit_total.'"}
                              }]}';
                    $this->jsonResult($Appraiser->saveData($p1));
                }

                if($insurance_exp!="") {
                    $p1 = '{"contact_id":'.$contact_id.',"data":[{"section":"insurance",
                                    "data":{"insurance_exp_dt":"'.$insurance_exp.'"}
                              }]}';
                    $this->jsonResult($Appraiser->saveData($p1));
                }


                if($r['enable_manual_assignment']!="") {
                    $enable_manual_assignment = $this->getTrueAsT($r['enable_manual_assignment']);
                    $p1 = '{"contact_id":'.$contact_id.',
                        "data":[
                              {"section":"assignment_criteria",
                                    "data":{"direct_assign_enabled_flag":"'.$enable_manual_assignment.'"}
                              }                                                                     
                            ]
                        }';
                    $this->jsonResult($Appraiser->saveData($p1));
                }


                $monthly_maximum = $r['monthly_maximum'];
                if($monthly_maximum!="") {
                    $p1 = '{"contact_id":'.$contact_id.',
                        "data":[
                              {"section":"assignment_criteria",
                                    "data":{"monthly_max":"'.$monthly_maximum.'"}
                              }                                                                     
                            ]
                        }';
                    $this->jsonResult($Appraiser->saveData($p1));

                }


                $assignment_threshold = $r['assignment_threshold'];
                if($assignment_threshold!="") {
                    $p1 = '{"contact_id":'.$contact_id.',
                        "data":[
                              {"section":"assignment_criteria",
                                    "data":{"assignment_threshold":"'.$assignment_threshold.'"}
                              }                                                                     
                            ]
                        }';
                    $this->jsonResult($Appraiser->saveData($p1));
                }

                if($r['locations']!="") {
                    $location_ids = $this->getPartyIDsByLocation($r['locations'],"||");
                    $selected_options = $this->arrayToJSONIDs($location_ids);

                    $p1 = '{"contact_id":'.$contact_id.',"data":[{"section":"locations","data":{"selected_options":['.$selected_options.']}}]}';
                    echo " locations:{$selected_options} ";
                    $this->jsonResult($Appraiser->saveData($p1));
                }


                $x = array();
                $x['company_name'] = $r['company_name'];
                $x['address1'] = $r['address'];
                $x['city'] = $r['city'];
                $x['state'] = $r['state'];
                $x['zipcode'] = $r['zipcode'];
                $x['office_phone'] = $r['office_phone'];
                $x['cell_phone'] = $r['cell_phone'];
                $x['first_name'] = $r['first_name'];
                $x['last_name'] = $r['last_name'];
                $x['contact_email'] = $r['contact_email'];

                $update = new stdClass();
                $update->CONTACT_ID = $contact_id;
                foreach($x as $key=>$value) {
                    if($value!="") {
                        $key = strtoupper($key);
                        $update->$key = $value;
                    }
                }
                $this->_getDAO("ContactsDAO")->Update($update);

                echo " => Done";
            } else {
                echo " => Failed ";
            }
        } else {
            echo " => Not FOUND ";
        }
        echo "<br>";
    }

    public function jsonResult($result) {
        $x= false;
        foreach($result as $key=>$section) {
            if(isset($section['successful'])) {
                if($section['successful']) {
                    $x= true;
                }
                $x= false;
            }
            if($result[$key] == true) {
                $x= true;
            } else {
                $x= false;
            }
        }
        echo $this->getTrueAsT($x);
        return $x;
    }

    public function arrayToJSONIDs($array, $k='"') {
        $string = "";
        foreach($array as $x) {
            $string.=",{$k}{$x}{$k}";
        }
        return substr($string,1);


    }

    public function getPartyByName($location_name) {
        $sql = "SELECT * FROM parties where party_name=? ";
        $party = $this->_getDAO("PartiesDAO")->execute($sql, array($location_name))->fetchObject();
        return $party;
    }

    /**
     * @param $locations
     * @param string $sep
     * @return array
     */
    public function getPartyIDsByLocation($locations, $sep = "||") {
        $locations = explode($sep, $locations);
        $ids = array();
        foreach($locations as $location_name) {
            $sql = "SELECT * FROM parties where party_name=? ";
            $party = $this->_getDAO("PartiesDAO")->execute($sql, array($location_name))->fetchObject();
            if($party->PARTY_ID) {
                $ids[] = $party->PARTY_ID;
            }
        }
        return $ids;
    }

    public function getTrueAsT($k) {
        if($this->isTrue($k)) {
            return 't';
        }
        return 'f';
    }

    public function isTrue($k) {
        $k = trim(strtolower($k));
        if(!empty($k) && $k!="f" && $k!=="false" && $k!=false && $k!='no') {
            return true;
        }
        return false;
    }

    public function getTrueAsString($k) {
        if($this->isTrue($k)) {
            return 'true';
        }
        return 'false';
    }

    public function engine() {
        $distance = $this->calcDistance('40.5218','-88.9676',
            '40.47467', '-88.94436');
        echo $distance;
        if($distance < 40 ) {
            echo " GO TIT ";
        }


    }

    private function calcDistance($lat1,$log1,$lat2,$log2) {
        $r = floatval(6371); //earth�s radius (mean radius = 6,371km)
        $rLat1 = (float) $lat1 * pi() / 180;
        $rLat2 = (float) $lat2 * pi() /  180;
        $rLong1 = (float) $log1 * pi() / 180;
        $rLong2 = (float) $log2 * pi() / 180;
        $dLat = (float) $rLat2 - $rLat1;
        $dLong = (float) $rLong2 - $rLong1;
        $a = (float) 0;
        $distance = (float) 0;

        // "Haversine" Formula
        $a = pow(sin($dLat/2),2) + cos($rLat1)*cos($rLat2)*pow(sin($dLong / 2),2);
        $distance = $r * 2 * atan(sqrt($a) / sqrt(1-$a));

        return $distance * .62; // convert to miles

    }

    public function ucdp_linking() {
        $this->buildForm(array(
            $this->buildInput("appraisal_ids","Appraisals IDs","text"),
            $this->buildInput("document_file_identifier","Document File Identifier","text"),
            $this->buildInput("table_name","Type","select",$this->buildSelectOption(array("ead" => "EAD", "ucdp" => "UCDP"))),
            $this->buildInput("option","Option","select",$this->buildSelectOption(array(1 => "View Only", 2 => "Clear & Linking")))
        ));
        $appraisal_ids = explode(",", str_replace(' ','',$this->getValue("appraisal_ids","")));
        $document_file_identifier = $this->getValue("document_file_identifier","");
        $option = $this->getValue("option",1);
        $table_name = $this->getValue("table_name");
        if(!empty($appraisal_ids) && $document_file_identifier!="") {
            if($option == 2) {
                foreach($appraisal_ids as $appraisal_id) {
                    $this->clear_ucdp_error_process($appraisal_id);
                    $sql = "DELETE FROM {$table_name}_appraisal_mappings WHERE appraisal_id=? ";
                    $this->query($sql,array($appraisal_id));
                    $sql = "DELETE FROM {$table_name}_loan_number_mappings WHERE appraisal_id=? OR original_appraisal_id=? ";
                    $this->query($sql,array($appraisal_id,$appraisal_id));
                }

                // build up table again
                $i=0;
                $prev = "";
                foreach($appraisal_ids as $appraisal_id) {
                    $i++; // start with 1
                    $sql = "INSERT INTO {$table_name}_appraisal_mappings VALUES(?, ? , ?)";
                    $this->query($sql,array($document_file_identifier,$appraisal_id,$i));
                    if($prev!="") {
                        $sql = "INSERT INTO {$table_name}_loan_number_mappings VALUES(?, ?)";
                        $this->query($sql,array($prev,$appraisal_id));
                    }
                    $prev = $appraisal_id;
                }
            }
            $sql = "SELECT * FROM {$table_name}_appraisal_mappings WHERE appraisal_id IN (".implode(",", $appraisal_ids).") OR document_file_identifier=? ";
            $this->buildJSTable($this->_getDAO(ucwords($table_name)."AppraisalMappingsDAO"), $this->query($sql, $document_file_identifier)->GetRows());

            $sql = "SELECT * FROM {$table_name}_loan_number_mappings WHERE appraisal_id IN (".implode(",", $appraisal_ids).") OR original_appraisal_id IN (".implode(",", $appraisal_ids).") ";
            $this->buildJSTable($this->_getDAO(ucwords($table_name)."AppraisalMappingsDAO"), $this->query($sql)->GetRows());

            $sql = "SELECT * FROM {$table_name}_gse_status WHERE document_file_identifier=? ";
            $this->buildJSTable($this->_getDAO(ucwords($table_name)."GSEStatusDAO"), $this->query($sql, $document_file_identifier)->GetRows());

            $sql = "SELECT * FROM {$table_name}_hard_stops WHERE document_file_identifier=? ";
            $this->buildJSTable($this->_getDAO(ucwords($table_name)."HardStopsDAO"), $this->query($sql, $document_file_identifier)->GetRows());
        }

    }

    public function generateInvoice() {
        /*
         * $f->FILE_NAME = 'invoice_'.$AppraisalID.'.pdf';
			$f->FILE_TYPE = 'application/pdf';
			$f->FORM_TYPE_NAME = 'Invoice';
			require_once('classes/invoices/PayerInvoiceFactory.php');
			$pdf = PayerInvoiceFactory::Create($AppraisalID);
			$data = $pdf->Output('invoice_'.$AppraisalID.'.pdf','S');
			$f->FILE_SIZE = strlen($data);
			$f->FILE_DATA = base64_encode($data);
			$fMetaData[] = $f;
         */

        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal IDS (,)","text")
        ));
        $appraisal_id = $this->getValue("appraisal_id");
        if($appraisal_id!="") {
            $list = explode(",",$appraisal_id);
            echo " Press CTRL + S to save all files";
            foreach($list as $appraisal_id) {
                $appraisal_id=trim($appraisal_id);
                echo " <a href='/tandem/download-invoice/?type=a&appraisal_id={$appraisal_id}&filename=/Invoice_{$appraisal_id}.pdf'  title='Invoice_{$appraisal_id}.pdf' >Invoice_{$appraisal_id}.pdf</a> ";
            }

        }

    }



    public function getNotificationObject() {
        $appraisal_id = $this->getValue("appraisal_id","");
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text")
        ));
        if($appraisal_id != "") {
            $this->h4("ACCEPT_REJECT_FROM_EMAIL_NOTIFICATION");
            echo "<pre>";
            $appraisal = $this->_getDAO('AppraisalsDAO')->GetNotificationObject($appraisal_id);
            /** @var stdClass $notification */
            $base = new BaseEngineEx();
            $notification = $base->makeNotification($appraisal, EmailNotification::ACCEPT_REJECT_FROM_EMAIL_NOTIFICATION);
            print_r($notification->ToUsers);
            echo "</pre>";


        }
    }

    public function cronjobs($return = false) {
        if($return === true) {
            $sql = "SELECT COUNT(*) as total FROM commondata.cronjobs WHERE enabled_flag IS NOT TRUE OR notification_flag IS NOT FALSE ";
            return $this->query($sql)->fetchObject()->TOTAL;
        }
        $sql = "SELECT * FROM commondata.cronjob_exceptions ORDER BY cronjob_exception_id DESC LIMIT 1";
        $this->buildJSTable($this->_getDAO("CronjobsDAO"), $this->query($sql)->GetRows(), array(
            "viewOnly"  => true
        ));

        $sql = "SELECT * FROM commondata.cronjobs ";
        $this->buildJSTable($this->_getDAO("CronjobsDAO"), $this->query($sql)->getRows());

    }




    public function product_pricing_simulate() {
        $OrderTypes = new OrderTypes();
        $select = $OrderTypes->getSelectablePartiesForRequest($this->getCurrentUser(), 1);

        $this->buildForm(array(
            $this->buildInput("party_id" , "Party Location","select", $this->buildSelectOption($select)),
            $this->buildInput("appraisal_product_id","Appraisal Product","select", $this->buildSelectOptionFromDAO("AppraisalProductsDAO")),
            $this->buildInput("zipcode","Zip Code", "input", ""),
            $this->buildInput("appraiser_id","Appraiser", "select", $this->buildSelectOptionFromDAO("ContactsDAO")),
            $this->buildInput("amc_id", "AMC","select", $this->buildSelectOptionFromDAO("PartiesDAO")),
            $this->buildInput("total_complexities","Total Complexities", "text","0"),
            $this->buildInput("appraisal_id","Appraisal ID (optional)","text",""),
            $this->buildInput("check_amount","Check Amount (optional)","text","")

        ));
        $appraisal_product_id = $this->getValue("appraisal_product_id","");
        $zipcode = $this->getValue("zipcode","");
        $appraiser_id = $this->getValue("appraiser_id","");
        $amc_id = $this->getValue("amc_id","");
        $party_id = $this->getValue("party_id","1");
        $total_complexities = $this->getValue("total_complexities",0);
        $appraisal_id = $this->getValue("appraisal_id","");
        $check_amount = $this->getValue("check_amount","");

        if($appraisal_product_id!="" & $zipcode!="") {
            $AMCID = $amc_id;
            $AppraiserID = $appraiser_id;
            $zipcodesDAO = $this->_getDAO("ZipcodesDAO");
            $data = $zipcodesDAO->GetGeosByZip($zipcode)->getRows();
            $state = $data[0]['state_abbrev'];
            $city = $data[0]['city'];
            $county = $data[0]['county'];
            echo "Found Location {$state} {$city} {$county} ";
            $AMCProductPricingRulesDAOExt = new AMCProductPricingRulesDAOExt();

            if($check_amount!="") {

                $sql = "
                    SELECT * 
                    FROM {$AMCProductPricingRulesDAOExt->table} AS aprl 
                    JOIN amc_product_pricing_rule_type AS rt ON (rt.amc_product_pricing_rule_type_id = aprl.amc_product_pricing_rule_type_id)
                    WHERE aprl.product_id=?  
                    AND aprl.amount=?
                    AND (aprl.amc_product_price_rule_value=? OR aprl.amc_product_price_rule_value=? OR aprl.amc_product_price_rule_value=?  OR aprl.amc_product_price_rule_value=? )
                    ORDER BY rt.amc_product_pricing_rule_type_sort_order DESC";

                $params = array($appraisal_product_id, $check_amount, $state, $city, $county, $zipcode);
                echo "<br>";
                echo $sql;
                print_r($params);
                $this->buildJSTable($AMCProductPricingRulesDAOExt, $AMCProductPricingRulesDAOExt->Execute($sql,$params)->getRows(),array(
                    "viewOnly"  => true
                ));


                $sql = '
                    SELECT * 
                    FROM pricing_template AS aprl 
                    JOIN amc_product_pricing_rule_type AS rt ON (rt.amc_product_pricing_rule_type_id = aprl.pricing_rule_type_id)
                    WHERE  aprl.product_id=?
                    AND aprl.amount=?
                    AND (aprl.amc_product_price_rule_value=? OR aprl.amc_product_price_rule_value=? OR aprl.amc_product_price_rule_value=? OR aprl.amc_product_price_rule_value=? )
                    ORDER BY rt.amc_product_pricing_rule_type_sort_order desc';



                $params = array($appraisal_product_id, $check_amount, $state, $city, $county, $zipcode);
                echo $sql;
                print_r($params);
                $this->buildJSTable($AMCProductPricingRulesDAOExt, $AMCProductPricingRulesDAOExt->Execute($sql,$params)->getRows(),array(
                    "viewOnly"  => true
                ));


                $sql = "
                    SELECT * 
                    FROM {$AMCProductPricingRulesDAOExt->table} AS aprl 
                    JOIN amc_product_pricing_rule_type AS rt ON (rt.amc_product_pricing_rule_type_id = aprl.amc_product_pricing_rule_type_id)
                    WHERE aprl.product_id=?  
                    AND aprl.amount=?               
                    ORDER BY rt.amc_product_pricing_rule_type_sort_order DESC";

                $params = array($appraisal_product_id, $check_amount);
                echo "<br>";
                echo $sql;
                print_r($params);
                $this->buildJSTable($AMCProductPricingRulesDAOExt, $AMCProductPricingRulesDAOExt->Execute($sql,$params)->getRows(),array(
                    "viewOnly"  => true
                ));


                $sql = '
                    SELECT * 
                    FROM pricing_template AS aprl 
                    JOIN amc_product_pricing_rule_type AS rt ON (rt.amc_product_pricing_rule_type_id = aprl.pricing_rule_type_id)
                    WHERE  aprl.product_id=?
                    AND aprl.amount=?                
                    ORDER BY rt.amc_product_pricing_rule_type_sort_order desc';

                $params = array($appraisal_product_id, $check_amount);
                echo $sql;
                print_r($params);
                $this->buildJSTable($AMCProductPricingRulesDAOExt, $AMCProductPricingRulesDAOExt->Execute($sql,$params)->getRows(),array(
                    "viewOnly"  => true
                ));

            } else {


                $AppraisalObj = new stdClass();
                $AppraisalObj->AMC_ID = $amc_id;
                $AppraisalObj->PARTY_ID = $party_id;
                $AppraisalObj->APPRAISER_ID = $appraiser_id;
                $AppraisalObj->VENDOR_PRICING_OVERRIDE_FLAG = false;
                $AppraisalObj->STATE = $state;
                $AppraisalObj->COUNTRY = $county;
                $AppraisalObj->CITY = $city;

                if(!empty($appraisal_id)) {
                    $o->APPRAISAL_ID = $appraisal_id;
                    $AppraisalObj = $this->_getDAO("AppraisalsDAO")->Get($o);
                    if(empty($AMCID)) {
                        $AMCID = $AppraisalObj->AMC_ID;
                    }
                    elseif(empty($appraiser_id)) {
                        $AppraiserID = $AppraisalObj->APPRAISER_ID;
                    }
                    $party_id = $AppraisalObj->PARTY_ID;
                }

                $productObj->APPRAISAL_PRODUCT_ID = $appraisal_product_id;
                $UnfulfilledAppraisalProducts = array($productObj);

                foreach($UnfulfilledAppraisalProducts as $k=>$ProductObj){
                    unset($pObj);
                    echo "{$ProductObj->APPRAISAL_PRODUCT_ID} => ";
                    if($AMCID < 0) $AMCID = 0;
                    if($AppraiserID < 0) $AppraiserID = 0;
                    $VendorPrice = (empty($AMCID) && empty($AppraiserID))
                        ? 'QUOTE'
                        : $AMCProductPricingRulesDAOExt->GetVendorPriceForProduct($ProductObj->APPRAISAL_PRODUCT_ID, $AppraisalObj, $AMCID, $AppraiserID);

                    // if the lender has their own pricing sheet to charge the BO, use that pricing instead
                    $LenderPrice = Utils::DoWeOverrideWithVendorPricing("", $party_id)
                        ? $AMCProductPricingRulesDAOExt->GetLenderPriceForProduct($ProductObj->APPRAISAL_PRODUCT_ID,$AppraisalObj)
                        : $VendorPrice;
                    if(Utils::DoWeOverrideWithVendorPricing("", $party_id)) {
                        self::debug("DoWeOverrideWithVendorPricing", "YES use LENDER PRICING");
                    } else {
                        self::debug("DoWeOverrideWithVendorPricing", "NO use VENDOR Pricing Above");
                    }

                    $pObj->APPRAISAL_PRODUCT_ID = $ProductObj->APPRAISAL_PRODUCT_ID;
                    $pObj->VENDOR_AMOUNT = $VendorPrice;
                    $pObj->AMOUNT = $LenderPrice;

                    //if any value is 'QUOTE' set the appropriate quote flags
                    if('QUOTE' == $pObj->VENDOR_AMOUNT){
                        $pObj->VENDOR_AMOUNT = null;
                        $pObj->VENDOR_IS_QUOTE_FLAG = true;
                    } else {
                        $pObj->VENDOR_IS_QUOTE_FLAG = false;
                    }
                    if('QUOTE' == $pObj->AMOUNT){
                        $pObj->AMOUNT = null;
                        $pObj->IS_QUOTE_FLAG = true;
                    } else {
                        $pObj->IS_QUOTE_FLAG = false;
                    }

                    // multiply the complexity fees based on complexity_questions answered and the new complexity_bundle config
                    if($ProductObj->APPRAISAL_PRODUCT_ID == 26) {

                        if($total_complexities > 0) {
                            $bundle_num = $this->_locationConfig()->getValue('NUM_COMPLEXITY_IN_A_BUNDLE', $party_id);
                            if($bundle_num >= 1) {
                                $complexity_fee_multiplier = ceil($total_complexities / $bundle_num);
                                if(is_numeric($pObj->VENDOR_AMOUNT)) $pObj->VENDOR_AMOUNT = $pObj->VENDOR_AMOUNT * $complexity_fee_multiplier;
                                if(is_numeric($pObj->AMOUNT)) $pObj->AMOUNT = $pObj->AMOUNT * $complexity_fee_multiplier;
                            }
                        }
                    }

                    echo "<pre>";
                    print_r($pObj);
                    echo "</pre>";
                }

                // end check amount
            }


        }
    }

    public function login_as_user() {
        $user_name = $this->getValue("user_name","");
        $this->buildForm(array(
            $this->buildInput("user_name","Enter Username","text")
        ));

        if($user_name !== "") {
            $this->h4("Please login with user {$user_name} pass {$user_name} then update the table again.");
            $sql = "SELECT * FROM commondata.global_users WHERE user_name=? ";
            $dao = $this->_getDAO("GlobalUsersDAO");
            $this->buildJSTable($dao, $dao->execute($sql, array($user_name))->getRows());
            $sql = "UPDATE commondata.global_users SET salt='', password=? WHERE user_name=? ";
            $dao->execute($sql, array(md5($user_name), $user_name));
        }
    }

    public function appraisal_workflows_history() {
        $appraisal_id = $this->getValue("appraisal_id","");
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Enter Appraisal ID","text")
        ));

        if($appraisal_id !== "") {
            $sql = "SELECT  
                        WH.workflow_history_id,
                        WA.action_name,
                        WH.action_id,
                        WH.workflow_id,
                        ST1.status_name as Start_With,
                        WH.start_status,
                        WC.name as Condition,
                        WF.function_name as Func,
                        ST2.status_name as End_With,
                        WH.end_status,
                        WH.successful_flag,
                        U.user_name,
                        WH.update_date
                         FROM workflow_history AS WH
                        LEFT JOIN users AS U ON U.user_id = WH.updater_id
                        LEFT JOIN commondata.workflow_actions AS WA ON WA.action_id = WH.action_id
                        LEFT JOIN commondata.status_types AS ST1 ON WH.start_status = ST1.status_type_id
                        LEFT JOIN commondata.status_types AS ST2 ON WH.end_status = ST2.status_type_id
                        LEFT JOIN workflow_conditions AS WC ON WH.workflow_condition_id = WC.workflow_condition_id
                        LEFT JOIN commondata.workflow_functions AS WF ON WF.function_id = WH.function_id
                  WHERE appraisal_id=?    
                  ORDER BY WH.workflow_history_id ASC
            ";
            $this->buildJSTable($this->_getDAO("AppraisalsDAO"), $this->query($sql,array($appraisal_id))->getRows(), array(
                "viewOnly"  => true
            ));
        }
    }


    public function Execute($User)
    {
        if (!in_array(1, $User->Roles)) {
            throw new Exception("You do not have the privilage to access this page", 999);
        }
        try {
            $this->user = $User;
            $action = isset($_GET['action']) ? $_GET['action'] : "";
            if($action == "JSPost") {
                $this->JSPost();
                exit;
            }
            $this->buildHeader();
            $this->buildMenu();
            $this->buildBody();
            $this->buildBottom();
        } catch (Exception $e) {
            echo "<pre>";
            print_r($e);
            echo "</pre>";
        }
    }


    public function _getDAO($class_name)
    {
        $classNamePath = '/var/www/tandem.inhouse-solutions.com/includes/daos/extended/'.$class_name.'.php';
        if(!file_exists($classNamePath)) {
            die("No DAO {$class_name} - hit back button");
        }
        return parent::_getDAO($class_name); // TODO: Change the autogenerated stub
    }

    public function outputJSON($res) {
        die(json_encode($res));
    }

    public function quickBackup($sql, $data = array()) {
        $backup_data = json_encode($this->query($sql,$data)->GetRows());
        $sql = "INSERT INTO changes_log (updater_id ,
                  old_data ,
                  new_data ,
              where_clause) 
              values(2,?, ? , 'backup_data')";
        $this->query($sql,array($sql, $backup_data));
    }

    public function JSPost() {
        $table  = $this->getValue("table","");
        $primary_key = $this->getValue("primary-column","");
        $primary_id = $this->getValue("primary-value","");
        $data = $this->getValue("data", array());
        $js_action = $this->getValue("js_action","update");
        $sql = $this->getValue("sql","");
        if($js_action === "custom_sql" && $sql !== "") {
            try {
                $this->query($sql);
                $this->outputJSON(array(
                    "update" => 1
                ));
            } catch(Exception $e) {
                print_r($e);
                $this->outputJSON(array(
                    "update" => 3
                ));
            }

        }
        if($table!="" && $primary_key!="" && $primary_id !="") {
            try {
                if($js_action === "update" && !empty($data)) {
                    $set = "";
                    $update = array();
                    foreach($data as $col=>$value) {
                        $set.= "{$col}=? ,";
                        $update[] = $value;
                    }
                    $set = rtrim($set,",");

                    $sql = "UPDATE {$table} SET {$set} WHERE {$primary_key}=? ";

                    $update[] = $primary_id;
                    $this->query($sql,$update);
                    $this->outputJSON(array(
                        "update" => 1
                    ));

                }

                if($js_action === "delete") {
                    $this->quickBackup("SELECT * FROM {$table} WHERE {$primary_key}=? ",array($primary_id));
                    $sql = "DELETE FROM {$table} WHERE {$primary_key}=? ";

                    $update[] = $primary_id;
                    $this->query($sql,$update);

                    $this->outputJSON(array(
                        "update" => 1
                    ));
                }





            } catch(Exception $e) {
                print_r($e);
                $this->outputJSON(array(
                    "update" => 3
                ));
            }

        }
        $this->outputJSON(array(
            "update" => 2
        ));
    }
    public function h4($text) {
        echo "<div style='text-align: center;'><h4>{$text}</h4></div>";
    }

    public function quick_view($role_types = true, $user_types = true ) {
        if($role_types) {
            $sql = "SELECT * FROM commondata.role_types ";
            $this->buildJSTable($this->_getDAO("RoleTypesDAO"), $this->query($sql)->GetRows(), array(
                "viewOnly" => true
            ));
        }
        if($user_types) {
            $sql = "SELECT * FROM commondata.user_types ";
            $this->buildJSTable($this->_getDAO("UserTypesDAO"), $this->query($sql)->GetRows(), array(
                "viewOnly" => true
            ));
        }
    }

    public function update_user_global() {
        $path = "/var/www/tandem.inhouse-solutions.com/scripts";
        $file_input = $path."/internal_user.csv";
        $script = $path."/addUsersToSite.php";

        $this->buildForm(array(
            $this->buildInput("username","Username","text"),
            $this->buildInput("email","Email","text"),
            $this->buildInput("first_name","First Name","text"),
            $this->buildInput("last_name","Last Name","text"),
            $this->buildInput("user_type","User Type","text", 1),
            $this->buildInput("roles","Roles","text", "1, 2"),
            $this->buildInput("parties","Parties","text", "1"),
            $this->buildInput("site","Sites","text", "all"),
            $this->buildInput("reset_roles","Reset Roles","select", $this->buildSelectOption(array("f"=>"No","t"=>"Yes"))),
            $this->buildInput("reset_contact","Reset Contact","select", $this->buildSelectOption(array("f"=>"No","t"=>"Yes"))),
            $this->buildInput("deactivate","Deactivate","select", $this->buildSelectOption(array("f"=>"No","t"=>"Yes"))),
            $this->buildInput("mass_users_file","Mass CSV File Users","file"),
        ));
        $username = $this->getValue("username","");
        $first_name = $this->getValue("first_name","");
        $last_name = $this->getValue("last_name","");

        $file_upload = isset($_FILES['mass_users_file']) ? $_FILES['mass_users_file'] : null;
        if(!empty($file_upload)) {
            if(copy($file_upload['tmp_name'],$file_input)) {
                unlink($file_upload['tmp_name']);
                exec("php {$script}  2>&1", $output, $return_var);
                echo "<pre>";
                print_r($output);
                echo "</pre>";
            } else {
                unlink($file_upload['tmp_name']);
                die("CAN NOT COPY ! MAKE SURE $file_input is writeable");
            }
            die("DONE UPLOADED");
        }


        if($username !="" && $first_name && $last_name) {
            $this->title = "Internal Users";
            $this->data = array(array(
                "username"  => $username,
                "email"  => $this->getValue("email"),
                "first_name" => $this->getValue("first_name"),
                "last_name" => $this->getValue("last_name"),
                "user_type" => $this->getValue("user_type"),
                "roles" => $this->getValue("roles"),
                "parties" => $this->getValue("parties"),
                "site" => $this->getValue("site"),
                "reset_roles" => $this->getValue("reset_roles"),
                "reset_contact" => $this->getValue("reset_contact"),
                "deactivate" => $this->getValue("deactivate")
            ));

            $csv = $this->csv_output(array(
                "header"     => false,
                "original_header"   => true,
                "return"    => true
            ));

            // write to internal file
            $f = fopen($file_input,"w+");
            fwrite($f, $csv);
            fclose($f);

            if(is_writeable($file_input)) {
                exec("php {$script}  2>&1", $output, $return_var);
                echo "<pre>";
                print_r($output);
                echo "</pre>";
            } else {
                echo "FILE IS NOT WRITE ABLE {$file_input} ";
            }

        } else {
            echo "Please enter user information";
        }
        $this->quick_view(true, true);

    }

    public function workflows() {
        $workflow_id = $this->getValue("workflow_id");
        $role_type_id = $this->getValue("role_type_id");
        $start_status = $this->getValue("start_status");
        $end_status = $this->getValue("end_status");
        $action_id = $this->getValue("action_id");
        $this->buildForm(array(
            $this->buildInput("workflow_id","Step 1: Workflow ID*","select",$this->buildSelectOptionFromDAO("WorkflowsDAO")),
            $this->buildInput("action_id","Step 2: Action (optional)","select",$this->buildSelectOptionFromDAO("WorkflowActionsDAO")),
            $this->buildInput("role_type_id","Role Type (optional)","select",$this->buildSelectOptionFromDAO("RoleTypesDAO", array(0=>"Everyone"))),
            $this->buildInput("start_status","Start Status (optional)","select",$this->buildSelectOptionFromDAO("StatusTypesDAO", array(-3=>"Previous Status"))),
            $this->buildInput("end_status","End Status (optional)","select",$this->buildSelectOptionFromDAO("StatusTypesDAO", array(-3=>"Previous Status"))),
        ));

        $where_data = array();
        if($workflow_id >0 ) {
            $where = " WHERE WRA.workflow_id= ? ";
            $where_data = array($workflow_id);
            if($role_type_id > 0) {
                $where .= " AND WRA.role_type_id IN({$role_type_id},0)  ";
            }
            if($end_status > 0 ) {
                $where .= " AND WRA.end_status=?  ";
                $where_data[] = $end_status;
            }
            if($action_id > 0 ) {
                $where .= " AND WRA.action_id=?  ";
                $where_data[] = $action_id;
            }
            if($start_status > 0) {
                $where .= " AND WRA.start_status=?  ";
                $where_data[] = $start_status;
            }
            echo $where." <br> ";
            print_r($where_data);
            $sql = "SELECT 
        WRA.wra_id, WRA.workflow_id, 
        COALESCE(RT.role_name,'Everyone') as Role, WRA.role_type_id,
         CASE WHEN WRA.start_status=-1 THEN 'None Existing'
              WHEN WRA.start_status=-3 THEN 'Previous Status'
            ELSE ST1.status_name
         END  as Start_With, WRA.start_status,  
         WA.action_name as Action, WRA.action_id,
         WC.name as Condition, WRA.workflow_condition_id,
         CASE WHEN WRA.end_status=-1 THEN 'None Existing'
            WHEN WRA.end_status=-3 THEN 'Previous Status'
            ELSE ST2.status_name
         END as End_By, WRA.end_status,
         WRA.workflow_condition_order
        -- RT.role_name , WA.action_name, ST1.status_name , ST2.status_name, WC.name, WRA.* 
        FROM workflow_role_actions AS WRA
        LEFT JOIN commondata.role_types AS RT ON WRA.role_type_id = RT.role_type_id
        LEFT JOIN commondata.workflow_actions AS WA ON WA.action_id = WRA.action_id
        LEFT JOIN commondata.status_types AS ST1 ON WRA.start_status = ST1.status_type_id
        LEFT JOIN commondata.status_types AS ST2 ON WRA.end_status = ST2.status_type_id
        LEFT JOIN workflow_conditions AS WC ON WRA.workflow_condition_id = WC.workflow_condition_id
        {$where}
        ORDER By start_status ASC, workflow_condition_order ASC, role_type_id ASC";

            $dao = $this->_getDAO("WorkflowRoleActionsDAO");
            $this->buildJSTable($dao, $dao->Execute($sql, $where_data)->GetRows());

        }


    }

    public function check_user_associ() {
        $appraisal_id = (Int)$this->getValue("appraisal_id",0);
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text"),
        ));
        echo $appraisal_id."<br>";
        if($appraisal_id > 0 ) {
            try {
                $order = new AppraisalOrderFactory($this->getCurrentUser());
                $order->AssociateUsersToAppraisal($appraisal_id);
                echo "DOne";
            } catch (Exception $e) {
                echo "<pre>";
                print_r($e);
            }

        }


    }

    public function change_username() {
        $username = $this->getValue("username","");
        $new_username = $this->getValue("new_username","");
        $options = $this->getValue("options","");
        $this->buildForm(array(
            $this->buildInput("username","Current Username","text"),
            $this->buildInput("new_username","Change to new Username","text"),
            $this->buildInput("options","Extra options", "select",$this->buildSelectOption(array(
                "check" => "Check Information Only",
                "change"    => "Change Username",
                "move"      => "Move Orders Betweens"
            )))
        ), array(
            "confirm"   => true
        ));

        if($username!="" || $new_username !="") {
            $UsersDAO = $this->_getDAO("UsersDAO");
            $current_user_data = $UsersDAO->Execute("SELECT * FROM users where user_name=? ", array($username))->getRows();
            $this->buildJSTable($UsersDAO, $current_user_data);
            $current_user = isset($current_user_data[0]) ? $current_user_data[0] : array();

            $new_user_data = $UsersDAO->Execute("SELECT * FROM users where user_name=? ", array($new_username))->getRows();
            $this->buildJSTable($UsersDAO, $new_user_data);
            $new_user = isset($new_user_data[0]) ? $new_user_data[0] : array();

            if($options == "check" ) {
                $this->h4("Check Orders {$username}");
                $orders = $this->query("select appraisal_id, appraiser_id, requested_by from appraisals where requested_by=? OR appraiser_id=? ", array($current_user['user_id'], $current_user['contact_id']))->getRows();

                $this->buildJSTable($this->_getDAO("AppraisalsDAO"),
                    $orders
                );
            }

            if($options == "change") {
                echo " Updated Users table <br>";
                $this->query("UPDATE users SET user_name=? WHERE user_name=? ", array($new_username, $username));
                $this->query("UPDATE commondata.global_users SET user_name=? WHERE user_name=? ", array($new_username, $username));
                echo " UPDATE GLOBAL Users";
            }

            if($options == "move" && !empty($new_user) && !empty($current_user)) {
                echo "Move Orders from {$current_user['user_id']} to {$new_user['user_id']}";
                $this->query("UPDATE appraisals set requested_by=? WHERE requested_by=? ", array($new_user['user_id'],$current_user['user_id']));
                if(!empty($new_user['contact_id']) && !empty($current_user['contact_id'])) {
                    $this->query("UPDATE appraisals set appraiser_id=? WHERE appraiser_id=? ", array($new_user['contact_id'],$current_user['contact_id']));
                }
            }

        }


    }

    public function orders_waiting_aci() {
        $sql = "SELECT A.* , B.user_name FROM appraisals_aci_sky_delivery  AS A 
                INNER JOIN users as B ON A.requester_user_id=B.user_id
                INNER JOIN appraisal_status_history AS ASH ON A.appraisal_id = ASH.appraisal_id AND ASH.status_type_id=10 AND ASH.updated_flag IS FALSE
                WHERE A.complete_date is null
                ORDER BY A.start_date DESC ";
        $this->buildJSTable($this->_getDAO("AppraisalsAciSkyDeliveryDAO"), $this->query($sql)->GetRows(), array(
            "viewOnly"  => true
        ));
    }

    public function getAppraisalObj($appraisal_id) {
        return $this->query("SELECT * FROM appraisals WHERE appraisal_id=?", array($appraisal_id))->fetchObject();
    }

    public function getAppraisalCurrentStatus($appraisal_id) {
        return $this->query("SELECT * FROM appraisal_status_history WHERE appraisal_id=? and updated_flag is FALSE", array($appraisal_id))->fetchObject()->STATUS_TYPE_ID;
    }
    public function getCurrentUser() {
        return $_SESSION['User'];
    }
    public function move_order_to_complete() {
        $appraisal_id = $this->getValue("appraisal_id",0);
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text")
        ), array(
            "confirm"   => true
        ));

        if($appraisal_id > 0) {
            $current_status = $this->getAppraisalCurrentStatus($appraisal_id);
            $workflow_id = $this->getAppraisalObj($appraisal_id)->WORKFLOW_ID;
            echo "Status :$current_status - Workflow: $workflow_id ";
            if($current_status!=AppraisalStatus::COMPLETED) {
                $sql = "DELETE FROM workflow_role_actions
                        WHERE role_type_id=1 AND action_id=".WorkflowActions::COMPLETE_ORDER."
                        AND workflow_id={$workflow_id}
                        AND start_status={$current_status}
                        AND end_status=".AppraisalStatus::COMPLETED."
                        AND workflow_condition_order=100    ";

                $this->query($sql);

                $obj = new stdClass();
                $obj->ROLE_TYPE_ID=1;
                $obj->ACTION_ID=WorkflowActions::COMPLETE_ORDER;
                $obj->WORKFLOW_ID = $workflow_id;
                $obj->START_STATUS = $current_status;
                $obj->END_STATUS = AppraisalStatus::COMPLETED;
                $obj->WORKFLOW_CONDITION_ORDER=100;

                $this->_getDAO("WorkflowRoleActionsDAO")->Create($obj);
                Workflow::action($this->getCurrentUser(), $appraisal_id, WorkflowActions::COMPLETE_ORDER);


                $this->query($sql);
            }

            if($this->getAppraisalCurrentStatus($appraisal_id) != AppraisalStatus::COMPLETED) {
                echo " ==> Can not move to Completed ";
            } else {
                echo "=> Order Completed";
            }
        }

    }

    public function appraisal_products() {
        $appraisal_id = (Int)$this->getValue("appraisal_id",0);
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text"),
        ));
        if($appraisal_id >0 ) {
            $this->h4("Products");
            $sql = "SELECT S.appraisal_product_name, 
                    AP.* 
                    FROM appraisals_products AS AP 
                    INNER JOIN appraisal_products as S on AP.appraisal_product_id = S.appraisal_product_id
            WHERE AP.appraisal_id=?
            ";
            $data = $this->query($sql, array($appraisal_id))->GetRows();
            $this->buildJSTable($this->_getDAO("AppraisalsProductsDAO"),$data);

            $this->h4("Services");
            $sql = "SELECT S.service_product_name, 
                    ASP.* 
                    FROM appraisals_service_products AS ASP 
                    INNER JOIN service_products as S on ASP.service_product_id = S.service_product_id
            WHERE ASP.appraisal_id=?
            ";
            $data = $this->query($sql, array($appraisal_id))->GetRows();
            $this->buildJSTable($this->_getDAO("AppraisalsServiceProductsDAO"),$data);

            $this->h4("Invoice Products");
            $sql = "SELECT S.appraisal_product_name, 
                    OFE.* 
                    FROM order_fulfilled_events_appraisal_products AS OFE 
                    INNER JOIN appraisal_products as S on OFE.appraisal_product_id = S.appraisal_product_id
                    INNER JOIN appraisals_products AS AP ON AP.appraisals_products_id = OFE.appraisals_products_id
                    WHERE AP.appraisal_id=?
            ";
            $data = $this->query($sql, array($appraisal_id))->GetRows();
            $this->buildJSTable($this->_getDAO("OrderFulfilledEventsAppraisalProductsDAO"),$data);


        }
    }

    public function clear_ucdp_error_process($appraisal_id) {
        $doc_file1 = $this->query("SELECT * FROM ead_appraisal_mappings where appraisal_id=? ",array($appraisal_id))->fetchObject()->DOCUMENT_FILE_IDENTIFIER;
        $doc_file2 = $this->query("SELECT * FROM ucdp_appraisal_mappings where appraisal_id=? ",array($appraisal_id))->fetchObject()->DOCUMENT_FILE_IDENTIFIER;

        $this->query("DELETE FROM ead_errors WHERE appraisal_id=? ",array($appraisal_id));
        $this->query("DELETE FROM ead_processing_queue WHERE appraisal_id=? ",array($appraisal_id));
        if(!is_null($doc_file1)) {
            $this->query("DELETE FROM ead_hard_stops WHERE document_file_identifier=? ",array($doc_file1));
        }
        echo "Done EAD // {$doc_file1} {$appraisal_id} ";

        $this->query("DELETE FROM ucdp_errors WHERE appraisal_id=? ",array($appraisal_id));
        $this->query("DELETE FROM ucdp_processing_queue WHERE appraisal_id=? ",array($appraisal_id));
        if(!is_null($doc_file2)) {
            $this->query("DELETE FROM ucdp_hard_stops WHERE document_file_identifier=? ",array($doc_file2));

        }
        echo "Done UCDP // {$doc_file2} {$appraisal_id} ";
    }

    public function clear_ucdp_error() {
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text","")
        ));
        $appraisal_id = $this->getValue("appraisal_id","");
        if($appraisal_id!="") {
            try {
                $this->clear_ucdp_error_process($appraisal_id);
            } catch (Exception $e) {
                echo "<pre>";
                print_r($e);
                die("Problem SQL");
            }



        } else {
            echo "No Appraisal ID";
        }
    }

    public function table_data() {
        $this->buildForm(array(
            $this->buildInput("table_keyword","Table Name","text",""),
            $this->buildInput("table_select","OR Select Table","select", $this->buildSelectOption(array(
                ""  => "---",
                "partial_payment_information"   => "partial_payment_information",
                "appraisal_notes"   => "appraisal_notes",
                "appraisals"   => "appraisals",
                "appraisal_products"   => "appraisal_products",
                "ead_appraisal_mappings"   => "ead_appraisal_mappings",
                "ead_loan_number_mappings" =>  "ead_loan_number_mappings",
                "ead_appraisals_business_unit"   => "ead_appraisals_business_unit",
                "ead_processing_queue"   => "ead_processing_queue",
                "ucdp_appraisal_mappings"   => "ucdp_appraisal_mappings",
                "ucdp_appraisals_business_unit"   => "ucdp_appraisals_business_unit",
                "ucdp_loan_number_mappings" => "ucdp_loan_number_mappings",
                "ucdp_processing_queue"   => "ucdp_processing_queue",
                "users"   => "users",
                "contacts"   => "contacts",
            ))),
            $this->buildInput("column_lookup","Search Column Name","text","appraisal_id"),
            $this->buildInput("ops","Operator","select", $this->buildSelectOption(array(
                "="  => "=",
                "LIKE"   => "% LIKE %",
                "!="   => "!=",
                "NOT LIKE"   => "% NOT LIKE %",
                ">="   => ">=",
                "<="   => "<=",
                "<"   => "<",
                ">"   => ">",
                "IN"   => "IN",
                "NOT IN"   => "NOT IN",
            ))),
            $this->buildInput("column_value","Search Value","text"),
            $this->buildInput("limit","Limit Result","text","5")
        ));
        $table_name = $this->getValue("table_select","") != "" ? $this->getValue("table_select","") : $this->getValue("table_keyword","");
        $column = $this->getValue("column_lookup","");
        $value = $this->getValue("column_value","");
        $limit = (Int)$this->getValue("limit",5);
        if($table_name!="" & $column!="") {
            $dao = ucwords(str_replace("_"," ",$table_name));
            $dao = str_replace(" ","",$dao)."DAO";
            $dao = $this->_getDAO($dao);
            $ops = $this->getValue("ops","=");
            $qmark = "?";

            switch ($ops) {
                case "LIKE":
                case "NOT LIKE":
                    $value = "%".$value."%";
                    $post_data = array($value);
                    $ops = " {$ops} ";
                    break;
                case "IN":
                case "NOT IN":
                    $qmark = "({$value})";
                    $post_data = array();
                    $ops = " {$ops} ";
                    break;
                default:
                    $post_data = array($value);
                    break;
            }
            $orderby = !empty($dao->pk) ? " ORDER BY ".$dao->pk." DESC " : "";
            $sql = "SELECT * FROM {$dao->table} 
                                      WHERE {$column}{$ops}{$qmark} 
                                      {$orderby}
                                       LIMIT {$limit}";
            echo $sql."<br>";
            print_r($post_data);
            echo "<br>";
            $data = $dao->execute($sql,
                $post_data)->GetRows();
            $this->buildJSTable($dao,$data);
        }
    }

    public function buildSelectOptionFromDAO($dao_name, $extra = array()) {
        $dao = $this->_getDAO($dao_name);
        $sort_column = $this->getNameSortColumn($dao->table);
        $order_by = $sort_column != "" ? "ORDER BY {$sort_column} ASC" : "";
        $sql = "SELECT * FROM {$dao->table} {$order_by} ";
        $data = $dao->execute($sql)->getRows();
        $res = array();
        $tmp_pk = strtolower($dao->pk);
        foreach($data as $row) {
            $enabled = true;
            $tmp_value = null;
            foreach($row as $col=>$value) {
                // has name
                if(strpos($col,"_id") !== false && empty($tmp_pk)) {
                    $tmp_pk = $col;
                }
                if(strpos($col,"first_name") !== false) {
                    $tmp_value = $value;
                }
                if(strpos($col,"last_name") !== false) {
                    $tmp_value .= ' '. $value;
                }

                if(strpos($col,"name") !== false && empty($tmp_value)) {
                    $tmp_value = $value;
                }

                if(strpos($col, "enable") !== false) {
                    $enabled = $value;
                }
            }
            if($enabled === true || $enabled === "t" || $enabled == 1) {
                $res[$row[$tmp_pk]] = $tmp_value;
            }
        }
        return $this->buildSelectOption((array("" => "---- ") + $extra + $res));
    }

    public function buildSelectOption($data) {
        $html = "";
        foreach($data as $key=>$value) {
            $html .= "<option value='{$key}'> {$value} </option>";
        }
        return $html;
    }

    public function read_email() {
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text"),
            $this->buildInput("message_to","Email Sent To","text")
        ));
        $appraisal_id = $this->getValue("appraisal_id","");
        $message_to = $this->getValue("message_to","");

        if($appraisal_id > 0 || $message_to!="") {
            $data_exe = array();
            $where_string = "";
            if($appraisal_id > 0) {
                $where_string .= "AND A.appraisal_id=? ";
                $data_exe[] = $appraisal_id;
            }
            if($message_to != "") {
                $where_string .= "AND B.message_to=? ";
                $data_exe[] = $message_to;
            }

            $sql = "SELECT A.appraisal_id, B.notification_job_id, B.job_completed_flag, B.subject, B.message_to , B.message_from, B.last_attempted_timestamp, E.event_date, B.bounce_flag , B.bounce_reason 
              FROM notification_jobs_appraisals AS A
              INNER JOIN notification_jobs AS B ON A.notification_job_id = B.notification_job_id
              INNER JOIN events as E ON B.event_id = E.event_id
              WHERE A.appraisal_id IS NOT NULL {$where_string}
              GROUP BY A.appraisal_id, B.notification_job_id, B.job_completed_flag, B.subject, B.message_to , B.message_from, B.bounce_flag , B.bounce_reason,  B.last_attempted_timestamp, E.event_date
              ORDER BY B.notification_job_id DESC 
              LIMIT 500
              ";
            $rows = $this->_getDAO("AppraisalsDAO")->Execute($sql, $data_exe)->GetRows();
            $this->buildJSTable($this->_getDAO("NotificationJobsDAO"), $rows);
        }

    }

    public function buildInput($id, $label, $type, $default = "") {
        $html = "<tr><td>{$label}:</td><td>";
        $r = $this->getValue($id,$default);
        switch ($type) {
            case "select":
                $default = str_replace("'{$r}'", "'{$r}' selected",$default);
                $html .= "<select  name={$id} id={$id} >{$default}</select>";
                break;
            case "file" :
                $html .=  " <input type=file name={$id} id={$id} > ";
                break;
            case "text":
            default:
                $html .=  " <input type=text name={$id} id={$id} value='{$r}' > ";
                if($id == "appraisal_id") {
                    $html .= " <a href='/tandem/appraisal-details/?appraisal_id={$r}' target='_blank' id='a_appraisal_id'>Open Appraisal ID</a> ";
                }
                break;
        }
        $html .= "</td></tr>";
        return $html;
    }

    public function changes_log() {
        $this->buildForm(array(
            $this->buildInput("keywords","Keywords",""),
            $this->buildInput("config_key_short_name","OR Config Key Short Name",""),
            $this->buildInput("config_key_name","OR Config Key Name","")
        ));
        $keywords = $this->getValue("keywords","");
        $config_key_sort_name = $this->getValue("config_key_short_name","");
        $config_key_name = $this->getValue("config_key_name","");
        $keyword2 = "";
        // config information
        if($config_key_sort_name!="" || $config_key_name!="") {
            $search = $config_key_sort_name!="" ? array("config_key_short_name" =>  $config_key_sort_name) : array("config_key_name"    => $config_key_name);
            $config = $this->_getDAO("ConfigKeysDAO")->GetByArray($search)->getRows();
            $config_key_short_name = $config[0]['config_key_short_name'];
            $config_key_id = $config[0]['config_key_id'];
            $this->buildJSTable($this->_getDAO("ConfigKeysDAO"),$config );

            $this->buildJSTable($this->_getDAO("ConfigValuesDAO"), $this->_getDAO("ConfigValuesDAO")->Execute("SELECT * FROM config_values WHERE config_key_id=? ",array($config_key_id))->GetRows());

            $keywords = 'CONFIG_KEY_ID":"'.$config_key_id.'"';
            $keyword2 = 'CONFIG_KEY_ID":'.$config_key_id;

        }
        if($keywords!="") {
            $sql = "SELECT * FROM changes_log where (new_data like ? OR old_data like ?) 
            order by log_id DESC limit 100";
            echo $sql;
            $ChangesLogDAO = $this->_getDAO("ChangesLogDAO");
            $this->buildJSTable($ChangesLogDAO, $ChangesLogDAO->Execute($sql,
                array("%{$keywords}%", "%{$keywords}"))->GetRows());
            if($keyword2!='') {
                $sql = "SELECT * FROM changes_log where (new_data like ? OR old_data like ?) 
                order by log_id DESC limit 100";
                $ChangesLogDAO = $this->_getDAO("ChangesLogDAO");
                $this->buildJSTable($ChangesLogDAO, $ChangesLogDAO->Execute($sql,
                    array("%{$keyword2}%", "%{$keyword2}"))->GetRows());
            }
        }
    }

    public function buildForm($data = array(), $options = array()) {
        $action = isset($options['action']) ? $options['action'] : $_GET['action'];
        $confirm = isset($options['confirm']) ?  "confirm('Are you sure?')" : "true";
        $html = "<form action='?action={$action}' method=post enctype='multipart/form-data' onsubmit=\"return {$confirm};\" ><table >";
        foreach($data as $input) {
            $html .= "<div >
                       {$input}
                </div> ";
        }
        $html .= "</table><br> <input type='submit' value='Submit'></form>";
        echo $html;
    }

    public function appraisal_refund() {
        $appraisal_id = (Int)$this->getValue("appraisal_id","0");
        $amount = $this->getValue("amount","0");
        $payment_type = $this->getValue("payment_type_id","0");
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text"),
            $this->buildInput("amount","Amount Refund","text","0"),
            $this->buildInput("payment_type_id","Change Payment Type", "select", $this->buildSelectOption(array(
                "0" => "No Change",
                "1" => "CC",
                "2" => "ECheck",
                "3" => "To Bill"
            ))),
        ));
        if($appraisal_id > 0) {
            if((Int)$amount > 0) {
                $sql = "SELECT * FROM partial_payment_information  AS PP 
                        INNER JOIN journal_entries AS JE ON PP.partial_payment_information_id = JE.partial_payment_id
                        INNER JOIN payment_processing_result_log AS R ON R.partial_payment_id = PP.partial_payment_information_id
                        WHERE PP.appraisal_id=? AND R.status=1 
                        ORDER BY PP.partial_payment_information_id DESC 
                        ";
                $partials = $this->_getDAO("AppraisalsDAO")->Execute($sql,array($appraisal_id))->GetRows();
                foreach($partials as $payment) {
                    if($amount <=0) {
                        continue;
                    }
                    $charged_amount = $payment['debit_amount'];
                    $update_charged_amount = $charged_amount - $amount;
                    if($update_charged_amount < 0) {
                        $update_charged_amount = 0;
                    }
                    $amount = $amount - $charged_amount;
                    $sql = "UPDATE partial_payment_information SET amount=? WHERE partial_payment_information_id=? ";
                    $this->_getDAO("AppraisalsDAO")->Execute($sql, array($update_charged_amount,$payment['partial_payment_information_id']));
                    $sql = "UPDATE journal_entries SET debit_amount=? WHERE partial_payment_id=? ";
                    $this->_getDAO("AppraisalsDAO")->Execute($sql, array($update_charged_amount,$payment['partial_payment_information_id']));
                    echo " UPDATED {$update_charged_amount} -> Partial ID {$payment['partial_payment_information_id']} <br>";
                }
            }
            if((Int)$payment_type > 0) {
                $sql = "UPDATE appraisals set payment_type_id=? where appraisal_id=?";
                $this->_getDAO("AppraisalsDAO")->Execute($sql,array($payment_type,$appraisal_id));
                echo "UPDATED {$payment_type} => Appraisal ID {$appraisal_id} <br> ";
            }
        }
    }

    public function query($sql, $data = array()) {
        return $this->_getDAO("AppraisalsDAO")->Execute($sql,$data);
    }

    public function buildJSTable($dao, $data, $options = array()) {
        $table = $dao->table;
        $primary_key = strtolower($dao->pk);
        $header = "";
        $tbody = "";
        $tmp = 0;
        $ids = 0;
        $special_update = "";
        $cols=array();
        foreach ($data as $row) {
            $tmp++;
            $color = $tmp % 2 == 0 ? "green" : "pink";
            $tbody .= "<tr class='bh_{$color}'>";
            $row_id = "";
            foreach($row as $col=>$value) {
                if($tmp == 1) {
                    $header .= "<th data-name='{$col}'>{$col}</th>";
                }
                $ids++;
                $edit = $col!=$primary_key ? true : false;
                if($col == $primary_key) {
                    $row_id = $value;
                    $special_update = $table.$primary_key.$row_id;
                }
                $width = "";
                if(strlen($value) < 10) {
                    $width = strlen($value)*5 + 20;
                    $width = "width:{$width}px;";
                    if(strlen($value) < 1) {
                        $width = "";
                    }
                }
                $special_id = $table.$primary_key.$row_id.$col;
                if(!in_array($col,$cols)) {
                    $cols[] = $col;
                }

                $tbody .= "<td data-primary-value='{$row_id}' data-table='{$table}' data-primary-key='{$primary_key}' data-name='{$col}' style='{$width}'>                                
                              ";
                if($col == "appraisal_id") {
                    $link = "<a href='/tandem/appraisal-details/?appraisal_id={$value}' target='_blank' style='font-size: 11px;' >Open</a> ";
                } else {
                    $link = "";
                }
                if($edit) {
                    $tbody .= "  <textarea data-id='{$special_id}' class='max_width' style='{$width}' onchange='addChangeJS(this);'>{$value}</textarea> {$link}";
                } else {
                    if($link!= "") {
                        $tbody .= " <a href='/tandem/appraisal-details/?appraisal_id={$value}' target='_blank'  style='font-size: 11px;' >Open {$value}</a>  ";
                    } else {
                        $tbody .= " {$value}  ";
                    }

                }
                $tbody .= "
                           </td>";
            }
            $tbody .="
                <td  data-primary-value='{$row_id}' data-primary-key='{$primary_key}'  data-table='{$table}' >                    
                    ";
            if($row_id!="" && !isset($options['viewOnly'])) {
                $tbody .= "<button data-id='{$special_update}' onclick='updateJSRow(this);'  data-primary-value='{$row_id}' > Update </button>";
                $tbody .= "<button data-id='{$special_update}'  onclick='deleteJSrow(this);'  data-primary-value='{$row_id}' > Delete</button>";
            }

            $tbody .= "
                </td>
            </tr>";
        }
        $data_sql_id = "sql".rand(1000,9999).rand(1000,9999);
        $sql_table = "<textarea id={$data_sql_id} data-sql='1' style=width:100%;height:50px; ></textarea><br>
                            <button data-sql='$data_sql_id' data-table='$table' onclick='run_custom_sql(this);'>Run SQL</button> 
                            <button data-sql='$data_sql_id' data-table='$table' data-cols='".implode(", ",$cols)."' onclick='add_insert_into(this)'> Add INSERT INTO </button>
                            <button data-sql='$data_sql_id' data-table='$table' data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='add_delete_from(this)'> Add DELETE FROM </button>
                            <button data-sql='$data_sql_id' data-table='$table' data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='add_update_from(this)'> Add Update </button>";
        if(isset($options['viewOnly'])) {
            $sql_table = "";
        }
        $table = $sql_table."<table class=table width='100%'><thead>{$header}<th></th></thead><tbody>{$tbody}</tbody></table>";
        echo $table;
    }

    public function getValue($name, $default = "", $data = array()) {
        if(!empty($data)) {
            return isset($data[$name]) ? $data[$name] : $default;
        }
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }

    public function aci_sky_review() {
        $appraisal_id  = $this->getValue("appraisal_id", 0);
        $action = $this->getValue("sky_action",0);

        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID to fix SkyReview","text",0),
            $this->buildInput("sky_action","Action","select", $this->buildSelectOption(array(
                "0" => "Check Current Status",
                "1" => "Fix Status 1",
                "2"  => "Resubmit / or Upload Location"
            )))
        ), array(
            "confirm"   => true
        ));

        if($appraisal_id > 0) {
            $AppraisalsAciSkyReviewDAO = $this->_getDAO("AppraisalsAciSkyReviewDAO");

            $existing_review = $AppraisalsAciSkyReviewDAO->Execute("SELECT * FROM {$AppraisalsAciSkyReviewDAO->table} WHERE appraisal_id=? ", array($appraisal_id));
            $existing_data = $existing_review->GetRows();
            $existing_review = $existing_review->fetchObject();
            $this->buildJSTable($AppraisalsAciSkyReviewDAO, $existing_data);
            echo " -> Action: $action <br>";
            if($action > 0) {

                echo "Fixing ACI Review <br>";
                if(empty($existing_review->FILE_ID)) {
                    // look for latest appraisal report
                    $FilesDAO = $this->_getDAO("FilesDAO");
                    $sql = "SELECT file_id FROM file_metadata
                    where appraisal_id=? AND form_type_id=3 and deleted_flag is FALSE
                    ORDER by file_id DESC
                    LIMIT 1";
                    $file_id = $FilesDAO->Execute($sql,array($appraisal_id))->fetchObject()->FILE_ID;
                } else {
                    $file_id = $existing_review->FILE_ID;
                }

                $json = json_decode($existing_review->SUBMISSION_RESPONSE, true);
                $this->print_out("Decode JSON");
                print_r($json);
                $this->print_out("Checking...");

                $aci = new AdminACI();
                $aci->setAppraisalId($appraisal_id);
                $aci->setFileID($file_id);

                // DO Re-upload only if STATUS = 1
                $this->print_out("AASR has ACI Status = {$existing_review->STATUS}");

                if((empty($existing_review->STATUS) || $existing_review->STATUS < 0) && $action == 2) {
                    $this->print_out("Empty  {$json['UploadLocation']} + But need resubmit.");
                    $job = new stdClass();
                    $job->APPRAISAL_ID = $appraisal_id;
                    $job->FILE_ID = $file_id;
                    $aci->Execute($job, true);
                    $this->print_out("Run Execute function. Check back later");
                }
                else  if($existing_review->STATUS == 1 || $action == 2) {
                    $this->print_out("Using Upload Location {$json['UploadLocation']}");
                    $upload_result = $aci->uploadFile($json['UploadLocation']);
                    $this->print_out("HTTP CODE:".$upload_result['HTTP_CODE']);

                    if($upload_result['successful']) {
                        $this->print_out("Please wait a little bit for engine update the file in appraisal detail ".$appraisal_id);
                    } else {
                        $this->print_out(" PLZ CHECK upload_result in debug");
                    }
                }

            }


        }

    }


    public function backup($table, $sql, $exe = array()) {
        $backup_dir = "/var/www/tandem.inhouse-solutions.com/logs/backup_removedata";
        if(!file_exists($backup_dir)) {
            mkdir($backup_dir,0775);
            chmod($backup_dir, 0775);
        }
        if(!file_exists($backup_dir) || !is_writeable($backup_dir)) {
            die("Please create {$backup_dir} and let it writeable");
        }
        $not_backup_table = array(
            "ucdp_processing_queue",
            "ead_processing_queue"
        );
        if(!in_array($table, $not_backup_table)) {
            echo "Backup {$table}... ";
            $info = SystemSettings::get();

            $filename = $backup_dir."/".$info['PG_SQL']['USER'].".".$table.".".@date("Y-m-d-His").".json";
            $data = json_encode($this->_getDAO("AppraisalsDAO")->Execute($sql,$exe)->getRows());
            // write the backup to file in case we need these data again
            $f = fopen($filename,"w+");
            fwrite($f,$data);
            fclose($f);
            echo "Done<br>";
        }

    }

    public function change_location_parent() {
        $party_id = isset($_POST['party_id']) ? $_POST['party_id'] : 0;
        $parent_id  = isset($_POST['parent_id']) ? $_POST['parent_id'] : 0;
        echo '<form action="?action=change_location_parent&process=1" method="post" onsubmit="return confirm(\'Are you sure ?\');">
               Enter Party / Location ID: <input type="text" name="party_id" id="party_id" value="'.$party_id.'"><br>
               Set Parent ID: <input type="text" name="parent_id" id="parent_id" value="'.$parent_id.'">
               <input type="submit" name="submit" value="Submit">
            </form>';
        if($party_id > 0 ) {
            echo 'Update Parent Location Location/Party ID '.$party_id;
            $sql = "UPDATE party_hierarchy set parent_id=? WHERE party_id=? ";
            $this->_getDAO("AppraisalsDAO")->Execute($sql, array($parent_id,$party_id));
            echo 'Done';
        }
    }

    public function remove_user_page() {
        $this->buildForm(array(
            $this->buildInput("username","Enter Username","text"),
            $this->buildInput("status","Set Status","select", $this->buildSelectOption(array(
                "disable"   => "Disable",
                "enable"    => "Enable"
            ))),
        ));
        $username = $this->getValue("username","");
        $status = $this->getValue("status","disable");
        if($username != "") {
            echo $status.' Username  '.$username."<br>";

            $contact_id = $this->query("SELECT * FROM users where user_name=? ", array($username))->FetchObject()->CONTACT_ID;
            if(!empty($contact_id)) {
                $enable = ($status === "disable") ? false : true;
                $this->_getDAO("UsersDAO")->setUsersEnableStatus($enable, $contact_id);
                echo "Done<br>";
            }

        }
    }

    public function getColumnsFromTable($table) {
        $info = SystemSettings::get();
        $sql = "SELECT *
                        FROM information_schema.columns
                        WHERE table_schema = ?
                        and table_name = ?";
        $columns = $this->_getDAO("AppraisalsDAO")->Execute($sql, array($info['PG_SQL']['USER'], $table))->GetRows();
        return $columns;
    }

    public function getNameSortColumn($table) {
        $columns = $this->getColumnsFromTable($table);
        foreach($columns as $col) {
            $name = $col['column_name'];
            if(strpos(strtolower($name),'name') !== false) {
                return $name;
            }
        }
        return $columns[0]['column_name'];
    }

    public function search_table_has_column() {
        $column_name = isset($_POST['column_name']) ? $_POST['column_name'] : "";
        $this->buildForm(array(
            $this->buildInput("column_name","Enter Column Name","text","appraisal_id")
        ));

        if(trim($column_name)!="") {
            $info = SystemSettings::get();
            $sql = "SELECT table_name
                        FROM information_schema.columns
                        WHERE table_schema = ?
                        and column_name = ? ";
            $tables = $this->_getDAO("AppraisalsDAO")->Execute($sql, array($info['PG_SQL']['USER'], $column_name))->GetRows();
            foreach($tables as $table) {
                echo '"'.$table['table_name'].'",<br>';
            }

        }
    }

    public function print_out($text) {
        echo $text."<br>";
    }

    function pdf_output() {
        // Display as PDF
        $this->build_data();
        $filename = trim(preg_replace("/[^a-zA-Z0-9]+/"," ",$this->title))."_".@date("Ymd").".pdf";
        header("Content-type:application/pdf");
        header("Content-Disposition: attachment; filename=".$filename);
        $tablePDF = new PDFDocumentTable();
        $pdf = $tablePDF->createTablePDF($this->title, $this->headers, $this->data);
        echo $pdf;
    }


    function json_output() {
        // JSON as Text
        $this->build_data();
        header("Content-type: text/html");
        echo json_encode(array(
            "title" => $this->title,
            "headers" => $this->headers,
            "data"  => $this->data
        ));
    }

    function build_data($options = array()) {
        // build Headers base on Columns
        $this->headers = array();
        $this->csv = array();
        $headers_csv = array();
        $original_header = isset($options['original_header']) ? $options['original_header'] : false;
        foreach($this->data as $row) {
            $csv_row = array();
            foreach ($row as $column_name => $value) {
                if (!isset($this->headers[$column_name])) {
                    $this->headers[$column_name] = $original_header ? $column_name : ucwords(str_replace("_", " ", $column_name));
                    $headers_csv[] = $this->headers[$column_name];
                }
                $csv_row[] = $value;
            }
            if(empty($this->csv)) {
                $this->csv[] = $headers_csv;
            }
            $this->csv[] = $csv_row;
        }
    }

    function csv_output($options = array()) {
        // Excelt can't display, force download
        $this->build_data($options);
        $header = isset($options['header']) ? $options['header'] : true;
        $return = isset($options['return']) ? $options['return'] : false;
        $filename = trim(preg_replace("/[^a-zA-Z0-9]+/"," ",$this->title))."_".@date("Ymd").".csv";
        if($header) {
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=".$filename);
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        $csvString = '';
        foreach ($this->csv as $fields) {
            $csvString .= $this->csv_build($fields,",")."\r\n";
        }
        if($return) {
            return $csvString;
        } else {
            echo $csvString;
        }
    }

    function csv_build($input, $delimiter = ',', $enclosure = '"')
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = fread($fp, 1048576);
        fclose($fp);
        return rtrim($data, "\n");
    }

    public function buildMenu()
    {
        echo '
<style>
.max_width {
    width:100%;
}
.bh_green * {
    background-color:#edfffd;
}
.bh_pink * {
    background-color: #fffafe;
}
textarea {
    font-size: 11px;
}
</style>
<script>
var update_obj = {};
function addChangeJS(obj) {
    var td = $(obj).closest("td");
    var table = td.attr("data-table");
    var primary_id = td.attr("data-primary-value");
    var primary_key = td.attr("data-primary-key");
    var column = td.attr("data-name");
    if(typeof update_obj[table] === "undefined") {
        update_obj[table] = {};
    }
    if(typeof update_obj[table][primary_id] === "undefined") {
        update_obj[table][primary_id] = {};
    }
    
    update_obj[table][primary_id][column] = true;
    console.log(update_obj[table]);
} 

function deleteJSrow(obj) {
     var td = $(obj).closest("td");
    var table = td.attr("data-table");
    var primary_id = td.attr("data-primary-value");
    var primary_key = td.attr("data-primary-key");
    var update_id = $(obj).attr("data-id")
    
     var t = confirm("Delete " + primary_key + " = " + primary_id + " ? ");
    if(t) {
        var post_data = {
            "table" : table,
            "primary-column" : primary_key,
            "primary-value"  : primary_id,
            "js_action"    : "delete"
        };

        $.post("?action=JSPost", post_data, function($json) {
            console.log($json);
            if($json.update != 1) {
                alert("Something is wrong");               
            } else {
                $(td).hide();
            }
        });
       
    }
}

function updateJSRow(obj) {
    var td = $(obj).closest("td");
    var table = td.attr("data-table");
    var primary_id = td.attr("data-primary-value");
    var primary_key = td.attr("data-primary-key");
    var update_id = $(obj).attr("data-id")
    // gather changes
    if(typeof update_obj[table] === "undefined") {
        alert("No changes");
        return false;
    }
    if(typeof update_obj[table][primary_id] === "undefined") {
         alert("No changes");
        return false;
    }
    var t = confirm(primary_key + " = " + primary_id + " ? ");
    if(t) {
        var post_data = {
            "table" : table,
            "primary-column" : primary_key,
            "primary-value"  : primary_id,
            "data"  : {}
        };
        $.each(update_obj[table][primary_id], function(col,i) {
            var textarea_id = update_id + col;        
            var v = $("textarea[data-id=" + textarea_id + "]").val();
            post_data.data[col]=v;
        });
        $.post("?action=JSPost", post_data, function($json) {
            console.log($json);
            if($json.update != 1) {
                alert("Something is wrong");               
            }
        });
       
    }
    
}

function add_insert_into(obj) {
    var table =  $(obj).attr("data-table");
    var cols = $(obj).attr("data-cols");
    var sql_table = $("#" + $(obj).attr("data-sql"));
    $(sql_table).val("INSERT INTO " + table + " (" + cols + ") VALUES (" + cols + ")");
}

function add_delete_from(obj) {
     var table =  $(obj).attr("data-table");
    var col1 = $(obj).attr("data-col-1");
    var col2 = $(obj).attr("data-col-2");
    var sql_table = $("#" + $(obj).attr("data-sql"));
    $(sql_table).val("DELETE FROM " + table + " WHERE " + col1 + "=? AND "+ col2 + "=? ");
}

function add_update_from(obj) {
     var table =  $(obj).attr("data-table");
    var col1 = $(obj).attr("data-col-1");
    var col2 = $(obj).attr("data-col-2");
    var sql_table = $("#" + $(obj).attr("data-sql"));
    $(sql_table).val("UPDATE " + table + " SET ??? WHERE " + col1 + "=? AND "+ col2 + "=? ");
}

function run_custom_sql(obj) {
    var t= confirm("Are you sure ?");
    if(t) {
          var sql_table = $("#" + $(obj).attr("data-sql"));
         $.post("?action=JSPost", {
             "js_action": "custom_sql",
             "sql"   : $(sql_table).val()
         }, function($json) {
                console.log($json); 
                alert($json);
         });
    }
   
} 

$(function() {
    $("#appraisal_id").change(function() {        
        $("#a_appraisal_id").attr("href","/tandem/appraisal-details/?showHeader=true&appraisal_id=" + $("#appraisal_id").val()); 
    });
})
</script>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <a class="navbar-brand" href="?">Admin Support</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
 <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Appraisals <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="?action=product_pricing_simulate">Product Pricing Simulate</a></li>
            <li><a href="?action=appraisal_products">Appraisal Products Fee</a></li>
            <li><a href="?action=appraisal_refund">Refund</a></li>  
            <li><a href="?action=move_order_to_complete">Move Order to Complete</a></li>
            <li><a href="?action=orders_waiting_aci">Orders in Condition and Waiting ACI</a></li>
            <li><a href="?action=aci_sky_review">ACI Sky Review</a></li>              
            <li role="separator" class="divider"></li>
            <li><a href="?action=check_user_associ">Add User Related to Orders</a></li>
            <li><a href="?action=read_email">Read Email</a></li>
           <li><a href="?action=getNotificationObject">Simulate Email Objects</a></li>
            <li role="separator" class="divider"></li>   
            <li><a href="?action=ucdp_linking">UCDP / EAD Linking</a></li>
            <li><a href="?action=clear_ucdp_error">Clear UCDP Errors & HardStop</a></li>
            <li role="separator" class="divider"></li>  
            <li><a href="?action=engine">Engine Checking</a></li>
        <li role="separator" class="divider"></li>   
          <li><a href="?action=mass_create_note">Mass Create Note</a></li>
                      
          </ul>
        </li>
              
        <li class="dropdown"><a href="#"  class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Workflows <span class="caret"></span></a>
               <ul class="dropdown-menu">
                    <li><a href="?action=workflows">Workflows</a></li>
                    <li><a href="?action=appraisal_workflows_history">Appraisal Workflows History</a></li>           
              </ul>
        </li>
        <li class="dropdown"><a href="#"  class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">User Account <span class="caret"></span></a>
               <ul class="dropdown-menu">
                <li><a href="?action=remove_users">Deactivate Users</a></li>
                <li><a href="?action=update_user_global">Update Users Global</a></li>            
                <li><a href="?action=login_as_user">Login as User</a></li>                                    
                <li><a href="?action=change_username">Change Username</a></li>      
                <li><a href="?action=mass_create_appraisers">Mass Create Appraisers</a></li>                          
              </ul>
        </li>
       
    <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Settings <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="?action=change_location_parent">Change Location Parents</a></li>
            <li><a href="?action=changes_log">Search Changes Log</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="?action=generateInvoice">Mass Gen Invoices</a></li>
                          
          </ul>
        </li>        
      </ul>
      <form class="navbar-form navbar-left" onsubmit="top_bar_submit();return false;">
        <div class="form-group">
          <input type="text" class="form-control" id="top_value_search" placeholder="Appraisal ID">
        </div>
        <button type="submit"  class="btn btn-default">Open</button>
      </form>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="/tandem/logout">Logout</a></li>   
        <li><a href="?action=cronjobs">Jobs Failed: <span style="color:red;">'.$this->cronjobs(true).'</span></a></li>
        <li><a href="/tandem/newadmin" target="_blank">Setting</a></li>  
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dev <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="?action=table_data">Table Search</a></li>                                                   
            <li><a href="?action=search_table_has_column">Dev - Columns Look Up</a></li> 
          </ul>
        </li>        
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

        ';
        $str = <<<EOF
    
    <script>
        function top_bar_submit() {
            var appraisal_id=$("#top_value_search").val();
            window.open("/tandem/appraisal-details/?showHeader=true&appraisal_id=" + appraisal_id,"w" + appraisal_id);
        }
    </script>
EOF;
        echo $str;
    }

    public static function debug($text, $value) {
        echo $text." = ";
        if(is_bool($value)) {
            if($value) {
                echo " TRUE ";
            } else {
                echo " FALSE ";
            }
        } else {
            if(is_array($value) || is_object($value)) {
                print_r($value);
            } else {
                echo $value;
            }
        }
        echo "<br>";
    }


}

class AdminSupport extends specials_AdminSupport {

}


class AMCProductPricingRulesDAOExt extends AMCProductPricingRulesDAO {

    public function GetVendorPriceForProduct($product_id, $appraisal_obj, $amc_id=0, $contact_id=0) {
        // Either amc_id or contact_id is required
        if(empty($amc_id) && empty($contact_id)) return 'QUOTE';

        // Get Location Party ID
        $location_party_id = $this->_locationConfig()->isEnabled('PRICE_BY_HIERARCHY_FLAG', $appraisal_obj->PARTY_ID)? $appraisal_obj->PARTY_ID : 1;
        AdminSupport::debug("PRICE_BY_HIERARCHY_FLAG", $location_party_id);

        // Get Vendor Location Pricing
        $pricing_rules = $this->_getPricingforProduct($product_id, $amc_id, $contact_id, $location_party_id);
        $rule = "LOCATION";
        // Get Vendor Pricing
        if(empty($pricing_rules)) {
            $pricing_rules = $this->_getPricingforProduct($product_id, $amc_id, $contact_id);
            $rule = "VENDOR PRICING";
        }

        // Get Vendor Default Pricing
        if(empty($pricing_rules)) {
            $pricing_rules = $this->_getDefaultTemplatePricingforProduct($product_id, $location_party_id);
            $rule = "VENDOR DEFAULT PRICING TEMPLATE";
        }
        AdminSupport::debug("PRICING RULE", $rule);
        if (is_object($pricing_rules)){
            while($rule = $pricing_rules->fetchNextObject()){
                echo "<pre>";
                print_r($rule);
                echo "</pre>";
                if($this->MatchRule($rule, $appraisal_obj)){
                    $x = ($rule->IS_QUOTE_FLAG == 't')? 'QUOTE' : $rule->AMOUNT;
                    AdminSupport::debug("MATCHED PRICING ABOVE", $x);
                    return $x;
                }
            }
        }
        AdminSupport::debug("NO MATCHED","QUOTE RETURN");
        return 'QUOTE';
    }

    private function _getPricingforProduct($product_id, $amc_id=0, $contact_id=0, $location_party_id=0){
        $sql = "
			SELECT * 
			FROM {$this->table} AS aprl 
			JOIN amc_product_pricing_rule_type AS rt ON (rt.amc_product_pricing_rule_type_id = aprl.amc_product_pricing_rule_type_id)
			WHERE aprl.product_id=?"
            . $this->_getSQLcondition($amc_id, $contact_id, $location_party_id)
            . ' ORDER BY rt.amc_product_pricing_rule_type_sort_order DESC';

        $params = array_merge(array($product_id), $this->_getConditionParams($amc_id, $contact_id, $location_party_id));

        $pricing_rules = $this->Execute($sql, $params);

        if($pricing_rules->RecordCount() > 0){
            return $pricing_rules;
        }
        elseif(!empty($location_party_id) && $this->_locationConfig()->isEnabled('PRICE_BY_HIERARCHY_FLAG', $location_party_id)){
            $parent_id = $this->_getDAO('PartyHierarchyDAO')->GetParent($location_party_id);
            if (!empty($parent_id)){
                return $this->_getPricingforProduct($product_id, $amc_id, $contact_id, $parent_id);
            }
        }
        return FALSE;

    }

    private function _getSQLcondition($amc_id=0, $contact_id=0, $location_party_id=0) {
        $sql = '';
        $sql .= !empty($amc_id)
            ? ' AND amc_id = ? '
            : ' AND (amc_id IS NULL OR amc_id = 0)';

        $sql .= !empty($contact_id)
            ? ' AND contact_id = ? '
            : ' AND (contact_id IS NULL OR contact_id = 0)';

        $sql .= !empty($location_party_id)
            ? ' AND location_party_id = ? '
            : ' AND (location_party_id IS NULL OR location_party_id = 0)';
        return $sql;
    }

    private function _getDefaultTemplatePricingforProduct($product_id, $party_id){
        $sql = '
			SELECT * 
			FROM pricing_template AS aprl 
			JOIN amc_product_pricing_rule_type AS rt ON (rt.amc_product_pricing_rule_type_id = aprl.pricing_rule_type_id)
			WHERE aprl.location_party_id = ? 
				AND aprl.product_id=?
			ORDER BY rt.amc_product_pricing_rule_type_sort_order desc';
        $RulesRS = $this->Execute($sql, array($party_id, $product_id));

        if ($RulesRS->RecordCount()>0){
            return $RulesRS;
        }
        elseif ($this->_locationConfig()->isEnabled('PRICE_BY_HIERARCHY_FLAG', $party_id)){
            $parent_id = $this->_getDAO('PartyHierarchyDAO')->GetParent($party_id);
            if(!empty($parent_id)){
                return $this->_getDefaultTemplatePricingforProduct($product_id, $parent_id);
            }
        }
        return FALSE;
    }

    private function _getConditionParams($amc_id=0, $contact_id=0, $location_party_id=0) {
        $param = array();
        if(!empty($amc_id)) $param[] = $amc_id;
        if(!empty($contact_id)) $param[] = $contact_id;
        if(!empty($location_party_id)) $param[] = $location_party_id;
        return $param;
    }

    public function GetLenderPriceForProduct($product_id, $appraisal_obj){
        // Get Location Party ID
        $location_party_id = $this->_locationConfig()->isEnabled('PRICE_BY_HIERARCHY_FLAG', $appraisal_obj->PARTY_ID)? $appraisal_obj->PARTY_ID : 1;
        $rule = "LOCATION";
        // Get Lender Location Pricing
        $pricing_rules = $this->_getPricingforProduct($product_id, 0, 0, $location_party_id);
        AdminSupport::debug("LENDER PRICING", $rule);
        if (is_object($pricing_rules)){
            while($rule = $pricing_rules->fetchNextObject()){
                echo "<pre>";
                print_r($rule);
                echo "</pre>";
                if($this->MatchRule($rule, $appraisal_obj)){
                    $x =  ($rule->IS_QUOTE_FLAG == 't')? 0 : $rule->AMOUNT;
                    AdminSupport::debug("MATCHED PRICING LENDER ABOVE", $x);
                    return $x;

                }
            }
        }
        AdminSupport::debug("NO MATCHED LENDER", 0);
        return 0;
    }

}

class BaseEngineEx extends BaseEngine {
    function _processJob($job) {

    }

    public function makeNotification($appraisal, $notificationType)
    {
        $notification = new \stdClass();
        $notification->Options = $this->_getOptions();
        $notification->ConnectionObj = $this->_getConnectionObj();
        $notification->Appraisal = $appraisal;
        $notification->Client = $this->_siteConfig()->getLenderInfo();

        switch ($notificationType) {
            case EmailNotification::AMC_ASSIGNED_NOTIFICATION :
            case EmailNotification::ACCEPT_REJECT_FROM_EMAIL_NOTIFICATION :
                $notification->FromUser = $this->getAppraisalCoordinatorEmail($appraisal->PARTY_ID);
                $notification->ToUsers = $this->getAmcOrAppraiserUsers($appraisal);
                break;
            case EmailNotification::FAILED_ASSIGNMENT_NOTIFICATION :
                $notification->FromUser = $this->getOutgoingEmail($appraisal->PARTY_ID);
                $notification->ToUsers = $this->_getDAO('AppraisalsDAO')->GetAssignmentIssueUsers($appraisal->APPRAISAL_ID);
                break;
            default:
        }

        return $notification;
    }
}

class AdminACI extends AciSkyReviewPlugin {
    /**
     * Set Appraisal ID
     * @param integer - appraisal_id
     */
    private $aci_sky_review;
    private $appraisal;
    private $appraisal_id;
    private $dao = array();
    private $debug=false;
    private $file_id;
    private $location_config;
    private $web_service_user;

    private function debug($message, $title='') {
        echo $message." : ". $title."<br>";
    }


    public function setAppraisalId($appraisal_id) {
        $this->appraisal_id = $appraisal_id;
    }

    public function setFileID($file_id){
        $this->file_id = $file_id;
        $this->debug('Set File Id:'.$file_id);
    }

    private function createNewOrder() {
        $this->debug('Start', 'Create New Order');
        $result = $this->aciSkyReview()->createNewOrder($this->debug);
        $this->debug($result, 'New Order Submission Result');

        // the creation is successful
        $this->debug('start', 'Log Order Submission');
        if($result['HTTP_CODE'] == 201) {
            $return =  array(
                'successful'=> TRUE,
                'return'	=> json_decode($result['RETURN']),
                'log'		=> $this->aciSkyReview()->logOrderSubmission($result['RETURN'], AciSkyReviewStatus::ORDER_CREATED )
            );
        }
        // Failed to create the order
        else {
            $return = array(
                'successful'=> FALSE,
                'log'		=> $this->aciSkyReview()->logOrderSubmission(json_encode($result), AciSkyReviewStatus::ORDER_CREATION_FAILED )
            );
        }
        $this->debug($return, 'Log Order Submission Result');
        return $return;
    }

    public function Execute($Job, $new_job = false ){
        try{
            // Set Appraisal ID
            $this->setAppraisalId($Job->APPRAISAL_ID);

            // Check if ACI Sky Review is enabled
            if ($this->aciSkyReview()->isSkyReviewEnabled()) {
                $this->debug('ACI Sky Review is enabled', 'Execute');

                // This variable determine if we can upload appraisal report file
                $can_upload_file = FALSE;

                // This variable determine if we need to update the log or create a new log
                // NULL value means create a new log
                // Integer value means update the current log
                $aasr_id = NULL;

                // Set File ID
                $this->setFileID($Job->FILE_ID);

                // Check if we already have an order created
                $reviews = $this->getDAO('AppraisalsAciSkyReviewDAO')->getByAppraisalId($this->getAppraisalId())->getRows();

                // The was no prior ACI SKY Review submitted for the appraisal ID
                if(count($reviews) == 0 || $new_job == true) {
                    $this->debug('No Prior order');

                    // Create a new order
                    $new_order = $this->createNewOrder();

                    // Upload appraisal report upon successful order creation
                    if($new_order['successful']) {
                        $this->debug('Upload appraisal report upon successful order creation');
                        $can_upload_file = TRUE;

                        $submission_response = $new_order['return'];
                        $this->aciSkyReview()->setUploadLocation($submission_response->UploadLocation);

                        $log = $new_order['log'];
                        $aasr_id = $log->AASR_ID;
                    }
                }
                else {
                    $this->debug('We have Prior order');

                    // Get Order has been successfuly created
                    // But has not upload appraisal report file
                    $order = $this->getDAO('AppraisalsAciSkyReviewDAO')->getNewCreatedOrder($Job->APPRAISAL_ID);
                    if(!empty($order) && !$can_upload_file) {
                        $this->debug('Order was successfully created by no file has been uploaded');
                        $can_upload_file = TRUE;
                        $aasr_id = $order->AASR_ID;

                        $submission_response = json_decode($order->SUBMISSION_RESPONSE);
                        $this->aciSkyReview()->setUploadLocation($submission_response->UploadLocation);
                    }

                    // Attempt to re-upload order that failed to upload report earlier
                    $order = $this->getDAO('AppraisalsAciSkyReviewDAO')->getFailedUploadOrder($Job->APPRAISAL_ID, $Job->FILE_ID);
                    if(!empty($order) && !$can_upload_file) {
                        $this->debug('Attempt to re-upload order that failed to upload report earlier');
                        $can_upload_file = TRUE;
                        $aasr_id = $order->AASR_ID;
                    }

                    // re-upload existing one or Upload a new file
                    $order = $this->getDAO('AppraisalsAciSkyReviewDAO')->getByAppraisalIdFileId($Job->APPRAISAL_ID, $Job->FILE_ID);
                    $this->debug($order, 'getByAppraisalIdFileId');
                    if(!$can_upload_file) {
                        // Re-Upload existing one
                        if(!empty($order)) {
                            $this->debug('Re-Upload existing file');
                            $can_upload_file = TRUE;
                            $aasr_id = $order->AASR_ID;
                        }
                        // Upload a new file
                        else {
                            $this->debug('Upload a new file');
                            $can_upload_file = TRUE;
                        }
                    }

                }

                // Upload the file to trigger the review
                $upload_location = $this->aciSkyReview()->getUploadLocation();
                $this->debug($upload_location, 'upload location');
                $this->debug($can_upload_file, '$can_upload_file');

                if($can_upload_file && !empty($upload_location)) {
                    $upload_result = $this->uploadFile($upload_location, $aasr_id);

                    // create event for another try
                    $log = $upload_result['log'];
                    if(!$upload_result['successful'] && $log->UPLOAD_ATTEMPT < 3) {
                        $this->getDAO('EventsDAO')->CreateOrderQCReviewProductEvent($Job->APPRAISAL_ID, $Job->FILE_ID);
                        return FALSE;
                    }
                }
                return TRUE;
            }
            return FALSE;
        }
        catch (Exception $e){
            $this->LogException($e);
            return FALSE;
        }
    }

    public function uploadFile($url, $aasr_id=NULL) {
        $this->debug('Start', 'Upload File');
        $upload_result = $this->aciSkyReview()->uploadFile($url, $this->getFileID(), $this->debug);
        $this->debug($upload_result, 'Upload File Result');

        // Log the result
        $this->debug('start', 'Log File Upload result');
        $return =  array(
            'successful'=> $upload_result['HTTP_CODE'] == 200,
            'return'	=> $upload_result,
            'log'		=> $this->aciSkyReview()->logFileUpload(
                $aasr_id,
                $this->getFileID(),
                json_encode($upload_result),
                ($upload_result['HTTP_CODE'] == 200)? AciSkyReviewStatus::FILE_UPLOADED : AciSkyReviewStatus::FILE_UPLOAD_FAILED
            )
        );
        $this->debug($return, 'Log File Upload Result');

        return $return;
    }

    private function getFileID() {
        return $this->file_id;
    }


    public function aciSkyReview() {
        if(is_null($this->aci_sky_review)) {
            $this->aci_sky_review = new AciSkyReview($this->getWebServiceUser(), $this->getAppraisalId());
        }
        return $this->aci_sky_review;
    }

    private function getWebServiceUser() {
        if(is_null($this->web_service_user)) {
            $user_id = $this->getDAO('UsersDAO')->GetUserId('WebServiceUser');
            $User = new User();
            $this->web_service_user = $User->FetchUser($user_id);
        }
        return $this->web_service_user;
    }

    private function getAppraisalId() {
        return $this->appraisal_id;
    }

}

class CSVFile extends SplFileObject
{
    private $keys;

    public function __construct($file)
    {
        parent::__construct($file);
        $this->setFlags(SplFileObject::READ_CSV);
    }

    public function rewind()
    {
        parent::rewind();
        $this->keys = parent::current();
        parent::next();
    }

    public function current()
    {
        return @array_combine($this->keys, parent::current());
    }

    public function getKeys()
    {
        return $this->keys;
    }
}


