<?php

/*
install command as webdocs user

wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/AdminSupport.php -O /var/www/tandem.inhouse-solutions.com/includes/pages/specials/AdminSupport.php
wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/blankFile.txt -O /var/www/tandem.inhouse-solutions.com/scripts/internal_user.csv

wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/addUsersToSite.php -O /var/www/tandem.inhouse-solutions.com/scripts/addUsersToSite.php


 */
ini_set('include_path','.:../includes/:../includes/libs/:/usr/share/pear/:/var/www/tandem.inhouse-solutions.com/includes/:/var/www/tandem.inhouse-solutions.com/includes/libs/');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('max_execution_time', 3600);

set_time_limit(3600);
ini_set('memory_limit', -1);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once('pages/BasePage.php');
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
require_once('modules/remote/admin/locations/ManageInternalLocationVendorPanels.php');
require_once('classes/Transmitter.php');
require_once('modules/remote/admin/companies/ManageBrokerCompany.php');
require_once ('classes/AmcProductPricingRules.php');
require_once("classes/invoices/PayerInvoiceFactory.php");
require_once("classes/Configs/LocationConfig.php");
require_once('classes/Wallet.php');
require_once('modules/remote/admin/locations/ManageInternalLocation.php');
require_once('classes/GoogleSettings.php');

@include('Net/SFTP.php');

class specials_AdminSupport extends specials_baseSpecials
{
    var $user;
    protected $headers = array();
    protected $csv = array();
    protected $title = "";
    protected $data = array();
    var $argv = array();

    public function buildBody()
    {
	    $action = isset($_GET['action']) ? $_GET['action'] : "";
        switch ($action) {
	        case "deploy":
	        	$this->deploy();
	        	break;
            case "cronjobs":
                $this->cronjobs();
                break;
            case "ucdp_linking":
                $this->ucdp_linking();
                break;
            case "enable_products":
                $this->enable_products();
                break;
	        case "mass_sending_email":
	        	$this->mass_sending_email();
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
	        case "fixCompletedEmail":
	        	$this->fixCompletedEmail();
	        	break;
            default:
				if(method_exists($this,$action)) {
					$this->$action();
				}

                break;
			// importManageLocationPricing
	        // fixPricingExcelSheet
	        // testMe
	            //




        }


    }

    public function testQ() {
    	require_once('classes/utils/PricingImport.php');
		$file_name = "/tmp/pricing_953f4e7d0e2bf0a18d9c7f6c0a7d7007.csv";
		$pricing = new PricingImport();
		$pricing->countLines($file_name);
    }

    public function globalUpdateConfig() {
        foreach ($this->getAllSchema() as $schema=>$connection) {
             $sql = "SELECT COUNT(*) as total FROM config_values where config_key_id=626 and party_id=1 ";
             $total = $this->sqlSchema($schema,$sql)->fetchObject()->TOTAL;
             echo "{$schema} = {$total} ";
             if($total == 0) {
                $config_value = '["2","1"]';
                $sql = "INSERT INTO config_values (config_key_id, config_value, party_id) VALUES(626,?,1)";
                $this->sqlSchema($schema,$sql, array($config_value));
                echo " UPDATED ";
             }
             echo "<br>";
        }
    }

    public function fixConditionEmail() {


    	foreach ($this->getAllSchema() as $schema=>$connection) {
    		if($schema == "wintrustmortgage") {

		    }
		    $sql = "SELECT * FROM
					appraisal_status_history AS ASH
					where ASH.updated_flag=false
					AND ASH.status_type_id=10
					AND ASH.status_date > '2017-11-12'";
		    $appraisals = $this->sqlSchema($schema, $sql)->getRows();
		    foreach($appraisals as $ash) {
			    $appraisal_id = $ash['appraisal_id'];
			    echo "$schema | $appraisal_id | ";
			    $sql = "SELECT COUNT(NJA.notification_job_id) as TOTAL FROM
						notification_jobs_appraisals AS NJA
						INNER JOIN notification_jobs as NJ ON NJA.notification_job_id = NJ.notification_job_id
						WHERE NJA.appraisal_id=? 
						AND NJ.subject like 'Condition Status Update%'
						AND NJ.last_attempted_timestamp >=?		
						LIMIT 1				
						";
			    $jobs = $this->sqlSchema($schema,$sql, array($appraisal_id, $ash['status_date']))->fetchObject();
			    if($jobs->TOTAL > 0) {
				    echo " SENT ALREADY ";
			    } else {
				    echo " MISSING NOTIFICATION ";
				    $sql = "SELECT * FROM condition_notification_queue 
							where appraisal_id=?
							and conditions_id IS NOT NULL
							ORDER BY condition_notification_queue_id DESC
							LIMIT 1";
				    $condition = $this->sqlSchema($schema, $sql, array($appraisal_id))->FetchObject();
				    if($condition->CONDITIONS_ID > 0) {
				    	echo " | <B>RE-SEND NOW</B> ";
				    	$sql = "UPDATE condition_notification_queue SET is_sent_flag=false WHERE conditions_id=? and appraisal_id=? ";
				    	$this->sqlSchema($schema, $sql, array($condition->CONDITIONS_ID, $appraisal_id));
				    }  else {
				    	echo " | COULD NOT RESEND";
				    }
			    }
			    echo "<br>";
		    }
	    }
    }

    var $temp_pricing = array();

    protected function isCmd() {
    	return !empty($this->argv);
    }

    public function menu_tools_google_check_google_api() {
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text"),
            $this->buildInput("address_custom","OR Custom Address","text"),
        ));
        $appraisal_id = $this->getValue("appraisal_id");
        $address_custom = $this->getValue("address_custom");
        if(!empty($appraisal_id)) {
            $std = new stdClass();
            $std->APPRAISAL_ID = $appraisal_id;
            $appraisal = $this->_getDAO("AppraisalsDAO")->get($std);
            $address_custom = "{$appraisal->ZIPCODE}";
        }
        if(!empty($address_custom)) {
            echo "<pre>";
            print_r($this->GetGeo($address_custom,"", true));
            echo "</pre>";
        }
    }

    public function GetGeo($address, $input_token = "", $debug = false) {
        $ret = null;
        $Transmitter = new Transmitter();
        $returnValue = null;
        $address = urlencode($address);
        $google = GoogleSettings::get();
        // from @abdur as 12/21 , 2500 request per day
        $backup_token = 'AIzaSyAsWu9i84tiquv04pGDXPumQLSIhbJxTEA';
        // from @abdur as jan/2018 , 25,000 request per day
        $default_token = $google['token'];

        $geo_token = $input_token!= "" ? $input_token : $default_token;
        $url = "https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key={$geo_token}";
        $geo_failed = true;
        try{
            $ret = $Transmitter->FetchUrl($url);
        } catch(NetworkingException $ne){
        }

        if(200 == $ret['HTTP_CODE']){
            $json = json_decode($ret['RETURN']);
            if($debug) {
                print_r($json);
            }

            switch($json->status){
                case 'OK':
                case 'ZERO_RESULTS':
                    if('OK' == $json->status){
                        $returnValue->LATITUDE = $json->results[0]->geometry->location->lat;
                        $returnValue->LONGITUDE = $json->results[0]->geometry->location->lng;
                        foreach ($json->results[0]->address_components as $address_component)
                        {
                            if(in_array('administrative_area_level_2',$address_component->types))
                            {
                                $returnValue->COUNTY = $address_component->long_name;
                            }
                        }

                        if(!empty($returnValue->LATITUDE) && $returnValue->LATITUDE != 'NaN') {
                            $geo_failed = false;
                        }

                    } else {
                        $returnValue->LATITUDE = 'NaN';
                        $returnValue->LONGITUDE = 'NaN';
                        $returnValue->COUNTY = null;
                    }
                    break;
                default:
                    //mark error?  try again later?
                    break;
            }
        }

        if($geo_failed && $input_token == "") {
            // try 1 more time with backup token from google.ini
            return $this->GetGeo($address, $backup_token, $debug);
        }

        return $returnValue;
    }

    public function menu_appraisals_status_change_to_any_status() {
        $appraisal_id = $this->getValue("appraisal_id","");
        $status_type_id = $this->getValue("status_type_id","");
        $action_id = $this->getValue("action_id","");
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text"),
            $this->buildInput("action_id","Action ID","select",$this->buildSelectOptionFromDAO("WorkflowActionsDAO")),
            $this->buildInput("status_type_id","Status Type ID", "select", $this->buildSelectOptionFromDAO("StatusTypesDAO"))
        ), array(
            "confirm"   => true
        ));

        if($appraisal_id > 0 && $status_type_id > 0 && $action_id > 0) {
            $current_status = $this->getAppraisalCurrentStatus($appraisal_id);
            $workflow_id = $this->getAppraisalObj($appraisal_id)->WORKFLOW_ID;
            echo "Status :$current_status - Workflow: $workflow_id ";
            if($current_status!=$status_type_id) {
                $sql = "DELETE FROM workflow_role_actions
                        WHERE role_type_id=1 AND action_id=".$action_id."
                        AND workflow_id={$workflow_id}
                        AND start_status={$current_status}
                        AND end_status=".$status_type_id."
                        AND workflow_condition_order=-100    ";

                $this->query($sql);

                $obj = new stdClass();
                $obj->ROLE_TYPE_ID=1;
                $obj->ACTION_ID=$action_id;
                $obj->WORKFLOW_ID = $workflow_id;
                $obj->START_STATUS = $current_status;
                $obj->END_STATUS = $status_type_id;
                $obj->WORKFLOW_CONDITION_ORDER=-100;

                $this->_getDAO("WorkflowRoleActionsDAO")->Create($obj);
                Workflow::action($this->getCurrentUser(), $appraisal_id, $action_id);

                $this->query($sql);
            }

            if($this->getAppraisalCurrentStatus($appraisal_id) != $status_type_id) {
                echo " ==> Can not move to {$status_type_id} ";
            } else {
                echo "=> Order moved to {$status_type_id}";
            }
        }

    }

    // @todo
    public function menu_appraisals_report_find_appraisals_by_products() {
    	$sql = "select A.appraisal_id, ASH.status_date , A.street_number, A.street_name , A.city, A.state, A.zipcode, A.county, A.borrower1_first_name, A.borrower1_last_name, A.appraisal_appointment_time from 
				msiretail.appraisals_products AS AP 
				INNER JOIN msiretail.appraisals as A ON AP.appraisal_id=A.appraisal_id
				INNER JOIN msiretail.appraisal_status_history as ASH ON A.appraisal_id = ASH.appraisal_id AND ASH.status_type_id=0 AND ASH.status_date >= '2017-01-01' AND ASH.status_date <= '2017-07-31'
				where AP.appraisal_product_id IN (14)
				and AP.is_deleted_flag IS FALSE
				order by ASH.status_date ASC";
    }

    // @todo
    public function menu_appraisals_appraiser_check_appraiser_address() {
    	$appraisal_id = $this->getValue("appraisal_id","");
	    $contact_id = $this->getValue("contact_id","");
	    $company_id = $this->getValue("company_id","");
    	$this->buildForm(array(
    		$this->buildInput("appraisal_id","Appraisal ID","text", $appraisal_id),
		    $this->buildInput("contact_id","OR Contact ID","text", $contact_id),
		    $this->buildInput("company_id","OR Company ID","text", $company_id),
	    ));
    	if(!empty($appraisal_id)) {
			$sql = "SELECT EAR.* , ER.engine_type_id, ER.completed_dt,  FROM
					engine_request_appraisal AS ERA
					JOIN  engine_request AS ER ON EAR.engine_request_id = EAR.engine_request_id
					JOIN engine_event AS EE ON EE.engine_request_id = EAR.engine_request_id
					";
	    }
    }

    public function importManageLocationPricing() {
    	echo "Example: file=path/file.csv party_id=1 reset=t schema=firstlook  \n";
    	echo "Excel columns: product_name | state | county | zip | amount | is_quote | username | party_id | amc_name \n\n";

	    if($this->isCmd()) {
		    $file = $this->getValue("file","");
		    if($file!="" && !file_exists($file)) {
		    	$file = "/var/www/tandem.inhouse-solutions.com/".$file;
		    }
		    $party_id = $this->getValue("party_id",null);
		    $reset_rules = $this->getValue("reset","f");
		    $schema = $this->getValue("schema","");
		    DAOFactory::$scriptConnectionObj = $this->getConnection($schema);
		    $amc_id = null;
		    $contact_id = null;
		    $pricing = new AmcProductPricingRules();

		    if($file!="" && $schema!="" && !empty(DAOFactory::$scriptConnectionObj)) {
			    if($reset_rules == "t" && !empty($party_id)) {
				    $this->_getDAO('AMCProductPricingRulesDAO')->ClearRulesForLender($party_id);
				    $pricing->_createTopRule($amc_id,$contact_id, $party_id);
			    }
			    echo " Importing File {$file} => $party_id \n";
			    $delete = false;
			    $res = array();
			    $csv = $this->CSVToArray($file, $delete);

			    $total = count($csv);
			    $list_user = array();
			    $list_user_reset = array();
			    foreach($csv as $line_number=>$data) {
				    $location_party_id = isset($data['party_id']) ? $data['party_id'] : $party_id;
				    if(!empty($location_party_id)) {
				    	echo " PARTY {$location_party_id} | ";
				    }
				    $amc_id = null;
				    $contact_id = null;
				    $data['is_quote'] = trim($data['is_quote']) === "Y" || trim(strtoupper($data['is_quote'])) === "YES" ;
				    if(isset($data['username']) && $data['username']!="") {
						$username = trim($data['username']);
						if(!isset($list_user[$username])) {
							// find contact id
							$q = "SELECT * FROM users where user_name=? LIMIT 1";
							$list_user[$username] = $this->sqlSchema($schema, $q, array($username))->fetchObject();
						}
						$contact_id = $list_user[$username]->CONTACT_ID;
						if(empty($contact_id)) {
							echo " SKIP NO CONTACT {$username} \n";
							continue;
						} else {
							echo "{$username} | ID {$contact_id} | ";
						}

					    if($reset_rules == "t" && !isset($list_user_reset[$username])) {
						    $list_user_reset[$username] = true;
						    $pricing = new AmcProductPricingRules();
						    $this->_getDAO('AMCProductPricingRulesDAO')->ClearRulesForContact($contact_id, $location_party_id);
						    $pricing->_createTopRule($amc_id,$contact_id, $location_party_id);
					    }
				    }

				    echo "$line_number / $total | ". $data['product_name']." =>  ";

				    $product_id = $pricing->_getProductId($data['product_name']);
				    if(!$product_id) {
				    	echo " NO PRODUCT ";
				    } else {
				    	echo " Product {$product_id} | ";
				    }
				    $product_pricing_rule_type_id = $pricing->_getProductPricingRuleType($data['product_name'], $data['state'], $data['county'], $data['zip']);
				    $res = $pricing->createRule($data, $amc_id, $contact_id, $location_party_id);
				    if($res && $product_id) {
				    	echo " GOOD ";
				    } else {
				    	echo " BAD ";
				    }
				    echo "\n";

			    }
		    }
	    }
    }

    public function testMe() {
    	echo $this->getValue("hello","");
    }

    protected function getCmdVars() {
	    $p = $this->argv[2];
	    $res = array();
	    if($p) {
	    	$tmp = $this->argv;
	    	foreach($tmp as $k=>$line) {
	    		if($k<2) {
	    			continue;
			    }
	    		$line = explode("=",$line,2);
	    		$res[trim($line[0])] = trim($line[1]);
		    }
	    }
	    return $res;
    }

    public function fixPricingExcelSheet() {
    	$this->buildForm(array(
    		$this->buildInput("pricing_file","Upload Pricing CSV","file")
	    ));
		echo "<pre>";
	    $delete = true;
	    $pricing_file = isset($_FILES['pricing_file']) ? $_FILES['pricing_file'] : null;
	    if(!empty($this->argv)) {
	    	$pricing_file['tmp_name'] = $this->argv[2];
	    	echo " Importing File {$pricing_file['tmp_name']} \n";
		    $delete = false;
	    }
	    if (!empty($pricing_file) && $pricing_file['tmp_name']!="") {
		    $pricing_lines = array();
		    $res = array();
		    $csv = $this->CSVToArray($pricing_file['tmp_name'], $delete, true);
		    $original_csv = $csv;
		    $mark = array();
		    $output = array(array("Product","State","County","Zip","Appraisal Fee","Quote"));
		    foreach($csv as $key=>$pricing) {
		    	if(isset($mark[$key])) {
		    		continue;
			    }
		    	$product = $pricing['Product'];
		    	$state = $pricing['State'];
		    	$county = $pricing['County'];
		    	$zip = $pricing['Zip'];
		    	$appraisal_fee = $pricing['Appraisal Fee'];
		    	$quote = $pricing['Quote'];
			    if(!empty($this->argv)) {
			    	echo " {$key} => {$product} \n";
			    }

			    if($state !="" && !isset($pricing_lines[$product]) ) {
				    // State level
				    $step = $this->_searchProductLines($csv, $key, "Product", $pricing, $mark);
				    if($step == -1) {
					    // not found, create one
					    $res[] = array(
						    "Product" => $product,
						    "State" => "",
						    "County"    => "",
						    "Zip"   => "",
						    "Appraisal Fee" => "",
						    "Quote" => "Y"
					    );
				    } elseif($step >=0 ) {
					    // found it
					    $mark[$step] = true;
					    $res[] = $csv[$step];
				    }
				    $pricing_lines[$product] = array();
				    $output[] = array($product,"","","","","Y");
			    }
			    if($state !="" && $county!=""  && !isset($pricing_lines[$product][$state])) {
				    // county level
				    $step = $this->_searchProductLines($csv, $key, "State", $pricing, $mark);
				    if($step == -1) {
					    // not found, create one
					    $res[] = array(
						    "Product" => $product,
						    "State" => $state,
						    "County"    => "",
						    "Zip"   => "",
						    "Appraisal Fee" => "",
						    "Quote" => "Y"
					    );
				    } elseif($step >=0 ) {
					    // found it
					    $mark[$step] = true;
					    $res[] = $csv[$step];
				    }
				    $pricing_lines[$product][$state] = array();
				    $output[] = array($product,$state,"","","","Y");
			    }
			    // search product line
			    if($state !="" && $county!="" && $zip!="" && !isset($pricing_lines[$product][$state][$county])) {
				    // zip level
				    $step = $this->_searchProductLines($csv, $key, "County", $pricing, $mark);
				    if($step == -1) {
					    // not found, create one
					    $res[] = array(
						    "Product" => $product,
						    "State" => $state,
						    "County"    => $county,
						    "Zip"   => "",
						    "Appraisal Fee" => "",
						    "Quote" => "Y"
					    );
				    } elseif($step >=0 ) {
				    	// found it
						$mark[$step] = true;
						$res[] = $csv[$step];
				    }
				    $pricing_lines[$product][$state][$county] = array();
				    $pricing_lines[$product][$state][$county][] = $zip;
				    $output[] = array($product,$state,$county,"","","Y");
			    }
			    $mark[$key] = true;
			    $res[] = $pricing;
			    $output[] = array($product,$state,$county,$zip,$appraisal_fee,$quote);
		    } // end loop csv



		    $fp = fopen('/var/www/tandem.inhouse-solutions.com/logs/output_kb.csv', 'w+');
		    foreach ($output as $fields) {
			    fputcsv($fp, $fields);
		    }

		    fclose($fp);

	    } // end file
	    echo "</pre>";

    }

    private function _searchProductLines($original_csv, $key, $search_type, $pricing, array $mark) {
	    foreach($original_csv as $step=>$search) {
		    if($step!=$key && !isset($mark[$step])) {
			    if( $search['Product'] == $pricing['Product'] && $search_type == "Product" &&
			        $search['State']=="" && $search['County'] == "" && $search['Zip'] == "" ) {
				    // found product line
					return $step;
			    }
			    // search state
			    if( $search['Product'] == $pricing['Product'] && $search['State'] == $pricing['State']  && $search_type == "State"
			         && $search['County'] == "" && $search['Zip'] == "" ) {
				    // found product line
				    if(isset($mark[$step])) {
					    return -2;
				    }
				    return $step;
			    }
			    // search county
			    if( $search['Product'] == $pricing['Product'] && $search['State'] == $pricing['State']  && $search['County'] == $pricing['County']  && $search_type == "County"
			        && $search['Zip'] == "" ) {
				    // found product line
				    return $step;
			    }
		    }
	    }
	    return -1;
    }

    public function searchUsers() {
    	$username = $this->getValue("username","");
    	$email = $this->getValue("email","");
    	$roles = $this->getValue("roles","");
	    $user_types = $this->getValue("user_type","");
	    $schemas = $this->getAllSchema();
	    $schema_data = array("all" => "All Schema");
	    foreach($schemas as $schema=>$connection) {
		    $schema_data[$schema] = $schema;
	    }
    	$this->buildForm(array(
    		$this->buildInput("username","Username","text", $username),
		    $this->buildInput("email","or Email","text", $email),
		    $this->buildInput("user_type","or User Types (,) ","select", $this->buildSelectOptionFromDAO("UserTypesDAO")),
		    $this->buildInput("roles","or Roles ID (,)","select", $this->buildSelectOptionFromDAO("RoleTypesDAO")),
		    $this->buildInput("in_schema","in Schema","select", $this->buildSelectOption($schema_data)),
	    ));
	    $in_schema = $this->getValue("in_schema","all");

    	if($username!="" || $email!= "" || $roles!="" || $user_types !="") {
    		// start
		    $res = array();
		    foreach($this->getAllSchema() as $schema=>$connection) {
		    	if($in_schema!="all" && $in_schema!=$schema) {
		    		continue;
			    }
		    	$where  = "";
			    $where2  = "";
		    	if($username!="") {
		    		$where.= " AND U.user_name='".$username."' ";
			    }
			    if($email!="") {
				    $where.= " AND C.contact_email like '%".$email."%' ";
			    }

			    if($roles!="") {
				    $where2.= " AND TMP.roles_name like '%[{$roles}]%'";
			    }

			    if($user_types!="") {
				    $where.= " AND U.user_type IN ({$user_types}) ";
			    }

		    	$sql = "SELECT *
						FROM (
							SELECT U.user_id, U.contact_id, U.user_name, C.contact_email, C.first_name, C.last_name, UT.user_type_name, 
							array_to_string(array_agg(rt.role_name || '[' || ur.role_id || ']')::text[],', ') as roles_name,
							'{$schema}' as site
							 FROM users as U
							INNER JOIN contacts as C on U.contact_id = C.contact_id
							INNER JOIN users_roles as UR ON U.user_id = UR.user_id
							INNER JOIN commondata.role_types as RT ON RT.role_type_id =UR.role_id
							INNER JOIN commondata.user_types as UT ON UT.user_type_id = U.user_type
							
							WHERE 1 > 0
							{$where}
							GROUP BY u.user_id, U.contact_id, U.user_name, C.contact_email, C.first_name, C.last_name, UT.user_type_name	
						) AS TMP
						WHERE 1 > 0
						{$where2}				
					";
				$rows = $this->sqlSchema($schema, $sql)->getRows();

				foreach($rows as $row) {
					if(!isset($res[$row['user_name']])) {
						$res[$row['user_name']] = $row;
						$res[$row['user_name']]['site'] = "";
					}
					$res[$row['user_name']]['site'] .= $row['site']." ";

				}


		    }
		    $this->buildJSTable($this->_getDAO("UsersDAO"), $res, array("viewOnly"=>true ,"excel" => true));
	    }

    }

    public function needUpdateSystem() {
        $options = SystemSettings::get();
        $prod = $options['General']['Environment'] == 'prod';
        $md1 = $this->cacheGet("md1");
        if(empty($md1)) {
            $md1 = md5(trim(file_get_contents("https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/AdminSupport.php?".rand(1,9999))));
            $this->cacheSet("md1",$md1);
        }
        $md2 = $this->cacheGet("md2");
        if(empty($md2)) {
            $md2 = md5(trim(file_get_contents(__DIR__."/AdminSupport.php")));
            $this->cacheSet("md2",$md2);
        }

        if($md1!==$md2 && $prod) {
            echo " <br><br><h4> Need Update Support Tools - <A href='?action=menu_tools_delpoyment_update_support_tools'>Click Here</A></h4> <br><br>";
            echo "{$md1} VS {$md2}";
        }
    }

    public function getConfigSchemaValue($schema,$config_name, $party_id ) {
        $this->getAllSchema();
        require_once('daos/extended/PartyHierarchyDAO.php');
        $connexion = $this->connections[$schema]['connection'];
        $PartyHierarchyDAO = new PartyHierarchyDAO($connexion);
        $party_ids = $PartyHierarchyDAO->GetHierarchy($party_id);
        $party_ids[] = 1;

        $sql = 'SELECT CV.* FROM config_keys AS CK
                INNER JOIN config_values AS CV ON CK.config_key_id = CV.config_key_id
                WHERE CK.config_key_short_name=? ';
        $rows = $this->sqlSchema($schema, $sql, array($config_name))->getRows();
        $res = null;

        foreach($party_ids as $party_id) {
            foreach($rows as $row) {
                if($party_id == $row['party_id'] && $row['config_value']!='' && empty($res)) {
                    $res = $row['config_value'];
                }
            }
        }
        return $res;
    }

    public function testGetConfigSchema() {
        $schemas = $this->getAllSchema();
        foreach($schemas as $schema=>$connection) {
            echo $schema . "<br>";
            echo "<pre>";
            $v = $this->getConfigSchemaValue($schema,"SEND_BORROWER_APPRAISAL_REPORT",1267);
            echo $v;
            echo "</pre>";
            echo "<br>";

        }
    }

    public function quickBuild($title, $dao,$where = "", $data=array(), $options = array()) {
        $dao = $this->_getDAO($dao);
        if(!empty($where)) {
            $where = " WHERE {$where} ";
        }
        if(!empty($dao->pk)) {
            $order_by = "order by {$dao->pk} DESC";
        }
        $sql = "SELECT * FROM {$dao->table} {$where} {$order_by} ";

        if(!empty($title)) {
            echo "<hr><h3>{$title}</h3>";
        }
        $datax = $this->query($sql, $data)->GetRows();
        $this->buildJSTable($dao, $datax, $options);

        return $datax;
    }

    public function _getAppraisalStatus($appraisal_id) {
        return $this->quickBuild("Status History","AppraisalStatusHistoryDAO", "appraisal_id=?", array($appraisal_id),
            array(
                "hookData" => array(
                    "updated_flag"  => array(
                        "f" => "f = current"
                    ),
                    "status_type_id" => array(
                        "table"  =>  "status_types",
                        "column"    => "status_type_id",
                        "display"   => "status_name"
                    ),
                    "updater_id"    =>  array(
                        "table" => "users",
                        "column"    => "user_id",
                        "display"   => "user_name"
                    )
                )
            ));
    }
    public function menu_appraisals_order_pull_order() {
        $appraisal_id = $this->getValue("appraisal_id");
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text",$appraisal_id)
        ));

        if(!empty($appraisal_id)) {
            $appraisals = $this->quickBuild("Appraisals", "AppraisalsDAO", "appraisal_id=?", array($appraisal_id));
            $this->_getAppraisalStatus($appraisal_id);
            $holds = $this->quickBuild("Hold Reasons","HoldsDAO", "appraisal_id=?", array($appraisal_id), array(
                "hookData"  => array(
                    "hold_type_id" => array(
                        "table" => "hold_types",
                        "display"   => array("hold_type_desc","comment_required_flag")
                    )
                )
            ));
            $this->getAppraisalProducts($appraisal_id);

            $requested_by = $this->quickBuild("Requested By","UsersDAO", "user_id=?", array($appraisals[0]['requested_by']));
            $this->quickBuild(null,"ContactsDAO", "contact_id=?", array($requested_by[0]['contact_id']));
            $this->quickBuild("Appraisers","ContactsDAO", "contact_id=?", array($appraisals[0]['appraiser_id']));
            $this->quickBuild(null,"UsersDAO", "contact_id=?", array($appraisals[0]['appraiser_id']));
            $this->quickBuild("AMC","PartiesDAO", "party_id=?", array($appraisals[0]['amc_id']));
            $this->quickBuild("Payment Processing Log","PaymentProcessingResultLogDAO", "appraisal_id=?", array($appraisal_id));
            $this->quickBuild("Wallet","WalletPartialPaymentInformationDAO", "appraisal_id=?", array($appraisal_id));
            $this->quickBuild("Partial Payment","WalletPartialPaymentInformationDAO", "appraisal_id=?", array($appraisal_id));
            $this->_getAppraisalWorkFlowHistory($appraisal_id);
            $this->_getAppraisalEmail($appraisal_id, "");
        }
    }

    private function _doWeSendBorrowerReport($schema, $appraisal)
    {
        $appraisal_id = $appraisal->APPRAISAL_ID;
        $q = 'select count(appraisal_status_history_id) as status_count 
		        from appraisal_status_history 
		       where status_type_id=? and appraisal_id=?';

        $obj = $this->sqlSchema($schema, $q,array(AppraisalStatus::COMPLETED,$appraisal_id))->FetchNextObject();

        if($obj->STATUS_COUNT > 1 && $this->getConfigSchemaValue($schema,'NO_RESEND_BO_APP_REPORT', $appraisal->PARTY_ID) == 't') {
            return true;
        }


        $sendBorrowerReport = $this->getConfigSchemaValue($schema,'SEND_BORROWER_APPRAISAL_REPORT', $appraisal->PARTY_ID) == 't';
        $approveFinalReport = $this->getConfigSchemaValue($schema,'APPROVE_FINAL_APPRAISAL_REPORT', $appraisal->PARTY_ID) == 't';

        //Check is AMC_ID matches any of the config IDs
        $OverRideBorrowerConfigbyPartyID = $this->getConfigSchemaValue($schema, 'SEND_BORROWER_APP_RPT_BY_PARTY_ID', $appraisal->PARTY_ID) == 't';

        if (($sendBorrowerReport && !$approveFinalReport) || $OverRideBorrowerConfigbyPartyID) {
            return true;
        }
        return false;
    }


    public function _needToGenerateCompletedDocs($schema, $appraisal_id) {
        // Generate the Document if this is the first time reach completed status
        $q = 'select count(appraisal_status_history_id) as status_count 
		        from appraisal_status_history 
		       where status_type_id=? and appraisal_id=?';

        $obj = $this->sqlSchema($schema, $q,array(9,$appraisal_id))->FetchNextObject();

        if($obj->STATUS_COUNT == 1) {
            return true;
        }

        // If we completed the order more than once, ONLY proceed if we have a new appraisal report
        $sql = "
			SELECT file_id, upload_time
			FROM file_metadata as fm
			JOIN
			(
				SELECT appraisal_id, status_date
				FROM appraisal_status_history
				WHERE appraisal_id = ?
					AND status_type_id = 9
					AND updated_flag IS TRUE
				ORDER BY status_date DESC
				LIMIT 1
			) as ash ON (ash.appraisal_id = fm.appraisal_id)
			WHERE fm.form_type_id = 3
				AND fm.upload_time > ash.status_date";
        return count($this->sqlSchema($schema, $sql, array($appraisal_id))->getRows()) > 0;
    }

    public function format_date_time($time,$format = "m/d/Y") {
        return @date($format, strtotime($time));
    }

    public function menu_tools_utils_fix_completed_email_missing() {
        $schemas = $this->getAllSchema();
        $my_schemas = array(
            "all"   => "All schemas"
        );
        foreach($schemas as $schema=>$connection) {
            $my_schemas[$schema] = $schema;
        }
        $this->buildForm(array(
            $this->buildInput("date_time","From Date Time","text"),
            $this->buildInput("to_date_time","To Date Time","text"),
            $this->buildInput("appraisal_id","Appraisal ID (optional)","text"),
            $this->buildInput("search_in_schemas","Schemas (optional)","select", $this->buildSelectOption($my_schemas)),
            $this->buildInput("actionx","Action","select", $this->buildSelectOption(array(
                "--"    => "----",
                "view"  => "View Only",
                "send"  => "Send Complete Again",
                "clear" => "Clear Old Events"
            ))),
        ), array(
            "confirm"   => true
        ));
        $actionx = $this->getValue("actionx");
        $date_time = $this->getValue("date_time",@date("Y-m-d H:i:s","yesterday"));
        $look_appraisal_id = $this->getValue("appraisal_id","");
        $to_date_time = $this->getValue("to_date_time");
        $search_in_schemas = $this->getValue("search_in_schemas","all");
        if($actionx == "clear") {
            $schemas = $this->getAllSchema();
            foreach($schemas as $schema=>$connection) {
                $sql = "DELETE FROM events
                        WHERE event_id IN (
                            select E.event_id from 
                                        events  as E
                                        LEFT JOIN appraisal_status_updated_jobs AS ASHJ ON E.event_id = ASHJ.event_id
                                        where
                                        E.event_type_id=2
                                        AND event_date > (now() - interval '7 days')
                                        AND ASHJ.event_id is null              
                        )";
                $this->sqlSchema($schema, $sql);
                echo $schema."<br>";
            }
        }
        else if(in_array($actionx , array("send","view")) && (!empty($date_time)||(!empty($look_appraisal_id)))) {
            echo "Check From Time {$date_time}";

            foreach($schemas as $schema=>$connection) {
                if($schema == "schoolsfirstfcu" || $schema=="inhousesolutions1"
                    || ($search_in_schemas!= "all" && $schema!=$search_in_schemas)
                    ) {
                    continue;
                }
                echo $schema."<br>";
                $big_where = 'AND ASH.status_date >= ? ';
                $big_where_x = array($date_time);
                if(!empty($look_appraisal_id)) {
                    $big_where = " AND ASH.appraisal_id=? ";
                    $big_where_x = array($look_appraisal_id);
                }
                elseif(!empty($to_date_time)) {
                    $big_where.= ' AND ASH.status_date <= ? ';
                    $big_where_x[] = $to_date_time;
                }

                $sql = "SELECT Job.*, ASH.*
			FROM appraisal_status_history AS ASH
			LEFT JOIN appraisal_status_updated_jobs AS Job ON (JOB.appraisal_status_history_id = ASH.appraisal_status_history_id )
			where ASH.updated_flag IS FALSE 
			AND ASH.status_type_id=9
			{$big_where}
			ORDER BY ASH.status_date ASC ";
                $jobs = $this->sqlSchema($schema,$sql, $big_where_x)->GetRows();
                if(!empty($look_appraisal_id)) {
                    echo "<pre>";
                    print_r($jobs);
                    echo "</pre>";
                }
                foreach($jobs as $job) {
                    $appraisal_id = $job['appraisal_id'];
                    if(empty($appraisal_id)) {
                        continue;
                    }
                    echo $appraisal_id." || {$this->format_date_time($job['status_date'])} => ";
                    $appraisal = $this->sqlSchema($schema, "SELECT * FROM appraisals where appraisal_id= ?", array($appraisal_id))->fetchObject();
                    $party_id = $appraisal->PARTY_ID;
                    if($this->getConfigSchemaValue($schema,"SEND_BORROWER_APPRAISAL_REPORT", $party_id) !== "t") {
                        echo " Site Disabled Send Borrower Report <br>";
                        continue;
                    }


                    $borrower_email = $appraisal->BORROWER1_EMAIL;
                    if($borrower_email!="") {
                        echo " FOUND {$borrower_email} ";
                        // locate borrower email
                        $sql = "SELECT * 
					FROM notification_jobs_appraisals JA 
					INNER JOIN notification_jobs AS NJ ON JA.notification_job_id=NJ.notification_job_id 
					WHERE JA.appraisal_id= ? AND NJ.message_to=? AND 
					((NJ.body like '%Completed%' and NJ.subject like 'Status Updated%') OR (NJ.subject like 'Download report for%'))
					AND (NJ.last_attempted_timestamp >= ? OR NJ.target_date >= ?)
					ORDER BY JA.notification_job_id DESC
					LIMIT 1";
                        $email = $this->sqlSchema($schema, $sql, array($appraisal_id, $borrower_email, $job['last_attempted_timestamp'], $job['last_attempted_timestamp']))->fetchObject();
                        $should_send = false;

                        if($email->NOTIFICATION_JOB_ID) {
                            echo " SENT ALREADY {$this->format_date_time($email->LAST_ATTEMPTED_TIMESTAMP)}  ";
                            // locate conditions
                            if(substr($email->SUBJECT,0,strlen('Download report for')) == 'Download report for') {
                                echo " || MANUALLY SENT || ";
                            } else {
                                echo " || CNX SENT || ";
                            }
                        }
                        elseif($this->_needToGenerateCompletedDocs($schema,$appraisal_id) && $this->_doWeSendBorrowerReport($schema, $appraisal)) {
                            echo " NOT SEND ";
                            $should_send = true;
                        } else {
                            echo " NO NEED TO SEND ";
                        }

                        if($should_send == true && $actionx == "send") {
                            echo " <b>NOT SENT YET</b> -> Updated Null for Job {$job['appraisal_status_updated_job_id']} ";
                            $sql = "SELECT * FROM appraisal_status_updated_jobs WHERE appraisal_id=? AND appraisal_status_updated_job_id=? ";
                            $current = $this->sqlSchema($schema, $sql, array($appraisal_id,$job['appraisal_status_updated_job_id']))->fetchObject();
                            if(empty($job['appraisal_status_updated_job_id'])) {
                                echo " || Missing Job ";
                                $event_data = '<?xml version="1.0" encoding="UTF-8"?>
<Messages><Message><Appraisal id="'.$appraisal_id.'"><Status id="'.$job['appraisal_status_history_id'].'">9</Status></Appraisal></Message></Messages>';
                                $sql = "INSERT INTO events (EVENT_TYPE_ID, EVENT_DATE, EVENT_DATA) VALUES(2,now(),?)";
                                $this->sqlSchema($schema, $sql, array($event_data));

                                $sql = "SELECT event_id FROM events order by event_id DESC LIMIT 1 ";
                                $event_obj = $this->sqlSchema($schema, $sql)->fetchObject();
                                echo " EVENT Created {$event_obj->EVENT_ID} ";
                            }  else {
                                echo $current->JOB_COMPLETED_FLAG;
                                if(is_null($current->JOB_COMPLETED_FLAG) && $current->JOB_COMPLETED_FLAG!=true) {
                                    echo "<i> Already NULLED </i> ";
                                } else {
                                    // doing update
                                    $sql = "UPDATE appraisal_status_updated_jobs set job_completed_flag=null WHERE appraisal_id=? AND appraisal_status_updated_job_id=? ";
                                    $this->sqlSchema($schema, $sql, array($appraisal_id,$job['appraisal_status_updated_job_id']));
                                    echo "<b> DONE </b>";
                                }
                            }

                        } elseif ($should_send == true) {
                            if(empty($job['appraisal_status_updated_job_id'])) {
                                echo " || EMPTY EVENT || ";
                            }
                            echo " <b>NOT SENT YET Job</b> {$job['appraisal_status_updated_job_id']} ";
                        }

                    } else {
                        echo " NO BORROWER EMAIL ";
                    }
                    echo "<br>";
                }



            }
        }

    }

    public function deploy() {
    	$servers = array(
    		// web 1
    		array(
    			"ip"=> "10.146.70.25",
			    "user" => "kbui",
			    "password"  => "g0disl0ve"
		    ),
		    array(
		    	"ip"    => "10.146.70.29",
			    "user" => "kbui",
			    "password"  => "@g0disl0ve"
		    ),
		    array(
			    "ip"    => "10.146.70.26",
			    "user" => "kbui",
			    "password"  => "@g0disl0ve"
		    )
	    );

    	// $content = file_get_contents(__DIR__."/AdminSupport.php");
		foreach($servers as $server) {
			$sftp = new Net_SFTP($server['ip']);
			if (!$sftp->login($server['user'], $server['password'])) {
				exit('Login Failed');
			} else {
				echo "<br> LOGIN GOOD {$server['ip']} <br>";
				$file_remote = "/home/".$server['user']."/K.php";
				$path = __DIR__."/AdminSupport.php";
				$sftp->put($file_remote, $path, NET_SFTP_LOCAL_FILE);

				$ssh = new Net_SSH2($server['ip']);
				if (!$ssh->login($server['user'], $server['password'])) {
					exit('Login Failed');
				}
				$path = "/var/www/tandem.inhouse-solutions.com/includes/pages/specials/AdminSupport.php";
				$userGlobal = "/var/www/tandem.inhouse-solutions.com/scripts/internal_user.csv";
				 // $ssh->write('sudo su');


				// $ssh->write('sudo cp '.$file_remote.' '.$path);

				// $ssh->write('sudo chmod 0777 '.$path);

				// $ssh->write('sudo echo "" > '.$userGlobal);

				// $ssh->write('sudo chmod 0777 '.$userGlobal);


			}
		}




    }

    public function menu_appraisals_invoices_generate_lender_invoice() {
		$this->buildForm(array(
			$this->buildInput("appraisal_id","Appraisal ID","text")
		));
		$appraisal_id = $this->getValue("appraisal_id","");
		if(!empty($appraisal_id)) {
			if (DAOFactory::getDAO('AppraisalStatusHistoryDAO')->GetCurrentStatus($appraisal_id) == AppraisalStatus::COMPLETED) {
				$persistFile = true;
			}
			echo " DONE , please visit Appraisal Detail and refresh, get the file under File Section.";
			/** @var FPDF $pdf */
			$pdf = PayerInvoiceFactory::Create($appraisal_id, FormTypes::LENDER_INVOICE, $persistFile);
			@$pdf->Output('invoice_' . $appraisal_id . '_' . date('Ymdhis') . '.pdf', 'D');
		}
    }

    var $cache = array();
    public function cacheSet($key, $value, $time = 3600) {
        $_SESSION['ADMINSUPPORT'][$key] = array(
            "value" => $value,
            "time"  => @date("U") + $time
        );
        $this->cacheWrite();
    }

    public function cacheGet($key) {
        $this->cacheRead();
        $cache = isset($_SESSION['ADMINSUPPORT'][$key]) ? $_SESSION['ADMINSUPPORT'][$key] : null;
        if(empty($cache)) {
            return null;
        }
        if($cache['time'] <= @date("U")) {
            $this->cacheDelete($key);
            return null;
        }
        return $cache['value'];
    }

    public function cacheDelete($key) {
        unset($_SESSION['ADMINSUPPORT'][$key]);
        $this->cacheWrite();
    }

    public function cacheWrite() {
        // write to file;
    }

    public function cacheRead() {
        if(!isset($_SESSION['ADMINSUPPORT'])) {
            $_SESSION['ADMINSUPPORT'] = array();
        }
    }



    public function getTablesFromSchema($json = true) {
        $OPTIONS = SystemSettings::get();
        $key = "table_list";
        $x = $this->cacheGet($key);
        if(empty($x)) {
            $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema=? OR table_schema=? OR table_schema='commondata' 
                GROUP BY table_name
                order by table_name ASC";
            $x = $this->query($sql, array($OPTIONS['PG_SQL']['DBNAME'], $OPTIONS['PG_SQL']['USER']))->GetRows();
            $this->cacheSet($key,$x);
        }

        if($json == true) {
            echo json_encode($x);
        }
        return $x;
    }



	public function menu_appraisals_invoices_generate_vendor_invoice() {
		$this->buildForm(array(
			$this->buildInput("appraisal_id","Appraisal ID","text")
		));
		$appraisal_id = $this->getValue("appraisal_id","");
		if(!empty($appraisal_id)) {
			echo " DONE , please visit Appraisal Detail and refresh, get the file under File Section.";
			$pdf = PayerInvoiceFactory::Create($appraisal_id, FormTypes::VENDOR_INVOICE);
			@$pdf->Output('vendor_invoice_' . $appraisal_id . '_' . date('Ymdhis') . '.pdf', 'D');
		}
	}

    public function mass_sending_email() {
    	$username_list = $this->getValue("username_list","");
    	$appraisal_id = $this->getValue("appraisal_id","");
    	$original_subject = $this->getValue("subject","");
	    $send_from = $this->getValue("send_from","");
	    $original_body = $this->getValue("email_body","");

    	$this->buildForm(array(
    		$this->buildInput("username_list","Username List(,)","textarea",$username_list),
		    $this->buildInput("appraisal_id","Appraisal ID","text",$appraisal_id),
		    $this->buildInput("subject", "Subject","text",$original_subject),
		    $this->buildInput("send_from", "Send From","text", $send_from),
			$this->buildInput("email_body", "Email Body","textarea", $original_body)
	    ));

    	if($username_list!="" && $original_subject!="" && $original_body!="" && $send_from!="") {
    		$username_list = explode("\n",$username_list);

    		foreach($username_list as $k=>$username) {
    			$username =  trim(strtolower($username));
			    $user = $this->_getDAO("UsersDAO")->Execute("SELECT * FROM users as U 
										INNER JOIN contacts as C ON U.contact_id=C.contact_id 
										where U.user_name=? ",array($username))->FetchObject();
			    if(!empty($user->CONTACT_ID)) {
				    $contact_id = $user->CONTACT_ID;
				    $email = $user->CONTACT_EMAIL;
				    $this->print_out($username." - ".$contact_id ." - ".$email);
				    $email_body = str_replace(array(
				    	"[[user_name]]",
					    "[[first_name]]",
					    "[[last_name]]"
				    ), array(
				    	$username,
					    $user->FIRST_NAME,
					    $user->LAST_NAME
				    ), $original_body);

				    $subject = str_replace(array(
					    "[[user_name]]",
					    "[[first_name]]",
					    "[[last_name]]"
				    ), array(
					    $username,
					    $user->FIRST_NAME,
					    $user->LAST_NAME
				    ), $original_subject);

				    if($k == 0) {
				    	echo $email_body;
				    }

					$email_obj = new stdClass();
				    $email_obj->JOB_COMPLETED_FLAG = "null";
				    $email_obj->SUBJECT = $subject;
				    $email_obj->BODY = $email_body;
				    $email_obj->REPLY_TO = $send_from;
				    $email_obj->MESSAGE_FROM = $send_from;
				    $email_obj->MESSAGE_TO = $email;
				    if($appraisal_id!="") {
				    	$email_obj->APPRAISAL_ID = (Int)$appraisal_id;
				    }

				    $this->_getDAO("NotificationJobsDAO")->Create($email_obj);


				    echo "<hr>";
			    } else {
			    	echo " CAN NOT FIND ID FOR {$username} <br>";
			    }
		    }
	    }
    }

    public function enable_products() {
        $this->buildForm(array(
            $this->buildInput("appraisal_product_id","Appraisal Product ID","text"),
            $this->buildInput("loan_ids","Enable for Loan Type IDs (,)","text"),
            $this->buildInput("property_type_ids","Enable for Property Type IDs (,)","text"),
            $this->buildInput("occupancy_ids","Enable for Occupancy IDs (,)","text"),
        ));


	    $appraisal_product_id = $this->getValue("appraisal_product_id","");
        $loan_ids = $this->getValue("loan_ids","");
        $property_type_ids = $this->getValue("property_type_ids","");
        $occupancy_ids = $this->getValue("occupancy_ids","");

        if($appraisal_product_id!="") {
            if($loan_ids!="") {
                // appraisal_products_loan_type_mapping
                $loan = explode(",",$loan_ids);
                foreach($loan as $loan_type_id) {
                    $loan_type_id = (Int)trim($loan_type_id);
                    try {
                        $sql = "INSERT INTO appraisal_products_loan_type_mapping (appraisal_product_id, loan_type_id, enabled_flag) VALUES($appraisal_product_id,$loan_type_id, true)";
                        $this->query($sql);
                        echo " LOAN {$loan_type_id} <br>";
                    } catch (Exception $e) {
                        echo $sql."; <br>";
                    }
                }
            } // end loan
            if($property_type_ids!="") {
                $list = explode(",",$property_type_ids);
                foreach($list as $id) {
                    $id = (Int)trim($id);
                    try {
                        $sql = "INSERT INTO appraisal_products_property_type_mapping (appraisal_product_id, property_type_id, enabled_flag) VALUES($appraisal_product_id,$id, true)";
                        $this->query($sql);
                        echo " Property {$id} <br>";
                    } catch (Exception $e) {
                        echo $sql.";<br>";
                    }
                }
            } // end property
            if($occupancy_ids!="") {
                $list = explode(",",$occupancy_ids);
                foreach($list as $id) {
                    $id = (Int)trim($id);
                    if($property_type_ids!="") {
                        $list2 = explode(",",$property_type_ids);
                        foreach($list2 as $id2) {
                            $id2 = (Int)trim($id2);
                            try {
                                $sql = "INSERT INTO appraisal_products_property_occupancy_type_mapping (appraisal_product_id, property_type_id, occupancy_type_id, enabled_flag) VALUES($appraisal_product_id,$id2,$id, true)";
                                $this->query($sql);
                                echo " Occu {$id}/{$id2} <br> ";
                            } catch (Exception $e) {
                                echo $sql."; <br>";
                            }
                        }
                    } // end property
                }
            }
            echo "DONE";
        }

	    $sql = "SELECT * FROM loan_types where enabled_flag is true";
	    $this->buildJSTable($this->_getDAO("AppraisalsDAO"), $this->query($sql)->GetRows(), array(
		    "viewOnly" => true
	    ));
	    $sql = "SELECT * FROM property_types where enabled_flag is true";
	    $this->buildJSTable($this->_getDAO("AppraisalsDAO"), $this->query($sql)->GetRows(), array(
		    "viewOnly" => true
	    ));

	    $sql = "SELECT * FROM occupancy_types where enabled_flag is true";
	    $this->buildJSTable($this->_getDAO("AppraisalsDAO"), $this->query($sql)->GetRows(), array(
		    "viewOnly" => true
	    ));

	    $this->buildJSTable($this->_getDAO("AppraisalProductsDAO"),$this->_getDAO("AppraisalProductsDAO")->Execute("select appraisal_product_id, appraisal_product_name from appraisal_products where enabled_flag=false order by appraisal_product_id DESC ")->GetRows(), array("viewOnly"=>true , "excel"=>true));



    }
	var $connections = array();
    public function getAllSchema() {
	    $DirectoryHandle = opendir('/var/www/conf/tandem/');
	    $this->connections = array();
	    $k = 0;
	    if(empty($this->connections)) {
		    while ($FileName = readdir($DirectoryHandle)) {
			    if (preg_match('/\.ini$/i', $FileName)) {
				    $OPTIONS = parse_ini_file("/var/www/conf/tandem/$FileName", true);
				    $ConnectionObj->HOST = $OPTIONS['PG_SQL']['HOST'];
				    $ConnectionObj->USER = $OPTIONS['PG_SQL']['USER'];
				    $ConnectionObj->PASSWORD = $OPTIONS['PG_SQL']['PASSWORD'];
				    $ConnectionObj->DBNAME = $OPTIONS['PG_SQL']['DBNAME'];
				    $ConnectionObj->OPTIONS = $OPTIONS;
				    $file = $ConnectionObj->USER;
				    $this->connections[$file]['connection'] = $ConnectionObj;
				    $this->connections[$file]['options'] = $OPTIONS;
				    unset($ConnectionObj);
				    $OPTIONS = array();
				    $k++;
			    }
		    }
	    }
	    return $this->connections;
    }

    public function getConnection($sql_user) {
    	$this->getAllSchema();
    	return $this->connections[$sql_user]['connection'];
    }

    public function sqlSchema($schema , $sql, $data = array()) {
	    $this->getAllSchema();
	    $connexion = $this->connections[$schema]['connection'];
	    $Generic = new GenericDAO($connexion);
	    return $Generic->Execute($sql,$data);
    }

    public function menu_workqueues_queue_workqueue_label() {
        $sql = "SELECT L.label_id, L.label_name, L.conditions_operator, L.conditions_logic
          FROM labels  AS L Where enabled_flag = true ";

        $labels = $this->query($sql)->getRows();
        foreach($labels as $i=>$label) {
            $label_id = $label['label_id'];
            $sql = "SELECT LC.label_condition_id, 
	                LF.label_field_name, 
	                LC.label_field_id ,
	                O.operator, LC.field_values_obj , CK.config_key_name as enabled_by_config, 
	                CK2.config_key_name as eabled_by_role_config
	              FROM  label_conditions AS LC 
                    LEFT JOIN label_fields AS LF ON LF.label_field_id = LC.label_field_id
                    LEFT JOIN config_keys aS CK on CK.config_key_id = LC.enable_config 
                    LEFT JOIN config_keys aS CK2 on CK2.config_key_id = LC.role_config 
                    LEFT JOIN operators as O ON O.operator_id = LC.operator_id
                    WHERE LC.label_id=?
                    ";
            $conditions = $this->query($sql,array($label_id))->GetRows();
            $label['enabled_by_config'] = "";
            $label['eabled_by_role_config'] = "";
            foreach ($conditions as $condition) {
                $condition_id = $condition['label_condition_id'];
                $condition['field_values_obj_text'] = $condition['field_values_obj'];
                switch($condition['label_field_name']) {
                    case "status_type_id":
                        $condition['field_values_obj_text'] = "";
                        $status = json_decode($condition['field_values_obj'],true);
                        if(!is_array($status)) {
                            $status = array($status);
                        }

                        foreach($status as $t) {
                            $status_name = $this->_getDAO("StatusTypesDAO")->getStatusNameByID(trim($t));
                            $condition['field_values_obj_text'].= ", {$status_name}[{$t}]";
                        }
                        $condition['field_values_obj_text'] = "(".trim(ltrim($condition['field_values_obj_text'],",")).")";
                        break;
                }
                $c = " {$condition['label_field_name']} {$condition['operator']} {$condition['field_values_obj_text']} ";

                if(!empty($condition['enabled_by_config'])) {
                    $label['enabled_by_config'] .= ", ".$condition['enabled_by_config'];
                }
                if(!empty($condition['eabled_by_role_config'])) {
                    $label['eabled_by_role_config'] .= ", " . $condition['eabled_by_role_config'];
                }
                switch ($label['conditions_operator']) {
                    case "MIX":
                        $label['conditions_logic'] = str_replace("::{$condition_id}::", $c,trim($label['conditions_logic']) );
                        $substr = 0;
                        break;
                    case "OR":
                        $label['conditions_logic'] .= "OR ".$c;
                        $substr = 2;
                        break;
                    case "AND":
                    default:
                        $label['conditions_logic'] .= "AND ".$c;
                        $substr = 3;
                        break;
                }
            }
            $label['enabled_by_config'] = trim(ltrim($label['enabled_by_config'],","));
            $label['eabled_by_role_config'] = trim(ltrim($label['eabled_by_role_config'],","));
            $label['conditions_logic'] = substr($label['conditions_logic'], $substr);
            $label['conditions_operator'] = "--REMOVE--";

            // get roles
            $sql = "SELECT * FROM label_roles as LR 
                    LEFT JOIN commondata.role_types as RT ON LR.role_id = RT.role_type_id
                    WHERE LR.label_id=? ";
            $roles = $this->query($sql, array($label_id))->getRows();
            $label['roles'] = "";
            foreach($roles as $role) {
                if(!empty($role['role_name'])) {
                    $label['roles'].= ", ".$role['role_name'];
                }
            }
            $label['roles'] = trim(ltrim($label['roles'],","));
            $labels[$i] = $this->rebuildArray($label);

        }

        $this->buildJSTable($this->_getDAO("LabelsDAO"), $labels, array(
            "viewOnly" => true
        ));
    }

    public function rebuildArray($array) {
        $res = array();
        foreach($array as $key=>$value) {
            if(!in_array($value, array("--REMOVE--"))) {
                $res[$key] = $value;
            }
        }
        return $res;
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

    public function menu_tools_files_fix_null_md5() {
    	$sql = "select file_id, md5 FROM file_metadata where md5 is null";
    	$files = $this->query($sql)->getRows();
    	foreach($files as $file) {
    		$file_id = $file['file_id'];
    		$md5 = md5($this->_getDAO('FilesDAO')->GetFileByID($file_id)->FILE_DATA);
    		$sql = "UPDATE file_metadata set md5=? where file_id=? ";
    		$this->query($sql,array($md5,$file_id));
    		echo "FILE ID {$file_id} => {$md5} <br>";
	    }
	    echo "DONE";
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

    public $geo_reset_cache = array();
    public function _addAppraiserGEO($data) {
        $username = $this->getValue("username","",$data);
        echo "{$username} => ";
        if($username!="") {
            $user = $this->_getDAO("UsersDAO")->Execute("SELECT * FROM users where user_name=? ", array($username))->FetchObject();
            $contact_id = $user->CONTACT_ID;

	        $address1 = $this->getValue(array("address1","address"),"",$data);
	        $address2 = $this->getValue("address2","",$data);
	        $city = $this->getValue("city","",$data);
	        $state = $this->getValue("state","",$data);
	        $zipcode = $this->getValue("zipcode","",$data);
	        $geo_radius = $this->getValue("geo_radius","",$data);
	        $county_name = $this->getValue(array("county_name","county"),"",$data);
	        $geo_type = $this->getValue("geo_type","",$data);
	        $reset_geo = $this->getValue("reset_geo","f",$data);
            if($this->isTrue($reset_geo) && !empty($contact_id) && !isset($this->geo_reset_cache[$contact_id])) {
                $this->geo_reset_cache[$contact_id] = true;
                $sql = "DELETE FROM contact_addresses WHERE contact_id=?  ";
                $this->query($sql, array($contact_id));
            }
            if (!empty($contact_id) && $geo_type!="") {
            	$sql = "DELETE FROM contact_addresses WHERE contact_id=? AND geo_type=? AND state=? AND county_name=? AND city=? AND zipcode=? AND address1=? ";
            	$this->query($sql, array(
            		$contact_id, $geo_type, $state, $county_name, $city, $zipcode, $address1
	            ));

	            $p1 = json_encode(array(
		            "contact_id"    => $contact_id,
		            "data"          => array(array(
			            "section"   => "geopoints",
			            "data"      =>  array(
			            	"action"    => "add",
				            "geo_type"  => $geo_type,
				            "state" => $state,
				            "county_name"   => $county_name,
				            "city"      => $city,
				            "zipcode"   => $zipcode,
				            "geo_radius" => $geo_radius,
				            "address1"  => $address1,
				            "address2"  => $address2
			            )
		            ))
	            ));

	            echo " DONE ";
	            $Appraiser = new ManageAppraiserUser();
	            $x = $this->jsonResult($Appraiser->saveData($p1), $p1);

            }

        } else {
            echo "NOT FOUND";
        }
        echo "<br>";

    }

    public function CSVToArray($csv_file, $delete = true, $original = false) {
        $csv = new CSVFile($csv_file);
        $res = array();
        foreach ($csv as $row) {
            $tmp_user = array();
            if (!is_array($row)) {
                continue;
            }
			if($original === true) {
				$res[] = $row;
				continue;
			}
            foreach ($row as $key => $value) {
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
            $res[] = $tmp_user;

        }
        if($delete) {
	        @unlink($csv_file);
        }
        return $res;
    }


    public function setupCompany($companies) {
		foreach($companies as $company) {
			$class = $this->getValue("class","",$company);
			$company_name = $this->getValue("company_name","",$company);
			if($class!="" && $company_name!="") {
				try {
					$class = "Manage{$class}";

					$address = $this->getValue("address","",$company);
					$city = $this->getValue("city","",$company);
					$state = $this->getValue("state","",$company);
					$email = $this->getValue("email","",$company);
					$phone_number = $this->getValue("phone_number","",$company);
					$enabled = $this->getValue("enabled","f",$company);
					$prefer = $this->getValue("preferred","f",$company);
					$zipcode = $this->getValue("zipcode","",$company);
					// look up
					$sql = "SELECT * FROM companies Where company_name=? ";
					$existing = $this->_getDAO("CompaniesDAO")->Execute($sql,array($company_name))->fetchObject();
					$real_address = $address." $city {$state} ";
					if($zipcode == "") {
						$geo = $this->getAddressInfo($real_address);
						if(!empty($geo)) {
							$zipcode = $this->findGeoData($geo, "postal_code");
						}
					}

					echo "<br> {$company_name} => ";

					if($existing->COMPANY_ID) {
						echo "EXISTING ";
						$company_id = $existing->COMPANY_ID;
					} else {
						echo " ADDING ";
						$company_id = 'null';
					}
					$p1 = '{"company_id":'.$company_id.',"data":[{"section":"company_info","data":{"company_name":"'.$company_name.'","address1":"'.$address.'","address2":"","city":"'.$city.'","state":"'.$state.'","zipcode":"'.$zipcode.'","zipcode_extension":"","appraisal_notification_email":"'.$email.'","primary_contact":"","enabled_flag":"'.$enabled.'","ein_number":"","phone_number":"'.$phone_number.'","preferred_flag":"'.$prefer.'"}}]}';
					$company_class = new $class();
					$this->jsonResult($company_class->saveData($p1));
				} catch (Exception $e) {
					echo "<pre>";
					print_r($e);
					echo "</pre>";
				}


			} else {
				echo " {$company_name} NO CLASS ";
			}
			echo "<br>";
		}
    }

    public function findGeoData($address_info_result, $type = "postal_code") {
		foreach($address_info_result['results'][0]['address_components'] as $r) {
			if($r['types'][0] == $type) {
				return $r['short_name'];
			}
		}
		return '';
    }

    public function getAddressInfo($address) {
	    $Transmitter = new Transmitter();

	    $returnValue = null;
	    $address = urlencode($address);
	    $google = GoogleSettings::get();
	    $key = $google['token'];
	    $url = "https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key={$key}";
	    try {
		    $ret = $Transmitter->FetchUrl($url);
		    return json_decode($ret['RETURN'],true);
	    } catch(Exception $e) {
			return array();
	    }

    }

    public function getMappingFields($data = array(), $use_data_fields_only = false) {
    	if($use_data_fields_only) {
    		return $data;
	    }
    	$fields = array(
    		"username"  => "",
		    "class"     => "AppraiserUser",
		    "roles"     => 10,
		    "first_name"    => "", // or use full_name
		    "middle_initial" => "",
		    "last_name"     => "",
		    "email" =>  "",
		    "time_zone" => "-5",
		    "company_name"  => "",
		    "address"   => "",
		    "city"  => "",
		    "county"    => "",
		    "state" => "",
		    "zipcode"   => "",
		    "office_phone"  => "",
		    "cell_phone"    => "",

		    "mailing_address"   => "",
		    "mailing_city"  => "",
		    "mailing_state" => "",
		    "mailing_zipcode"   => "",
		    "ssn_ein"   => "",
		    "ein"   => "",
		    "location_ids"  => 1,

		    // panel
		    "panel_assigned" => "", // t f
	        "panel_weight"  => "",
		    "panel_location"    => "",
		    "panel_preferred"   => "", // t f

		    // loan types
		    "allowed_loan_types" => "", // or 1,2,3,4,5

		    // license
		    "fha" => "",
		    "license_state" => "",
		    "license_level" => "",
		    "license_exp"   => "",
		    "license_number"    => "",
		    "license_issue_dt"  => "",
		    "license_user_override_flag"    => "",
		    "license_eff_dt" => "",
		    "license_active_flag" => "",

		    // insurance
		    "insurance_carrier" => "",
		    "insurance_policy"  => "",
		    "insurance_exp" => "",
		    "insurance_effective_date"  => "",
		    "insurance_limit_total" => "",
		    "insurance_limit_per_claim" => "",

		    // assignment
		    "monthly_maximum"   => "",
		    "assignment_threshold"  => "",
		    "enable_manual_assignment"  => "",
		    "maximum_property_value"    => "",

		    // vendor setting
		    "process_payment" => "",
		    "require_review" => "",
		    "require_qc"  => "",
		    "enable_atr_generation"  => "",
		    "enable_borrower_welcome_email" => "",
		    "send_borrower_appraisal_report"  => "",

		    // locations
		   // "locations" => 1, // multi A||B

	    );
    	foreach($data as $key=>$value) {
    		$fields[$key] = $value;
	    }
    	return $fields;
    }

    public function menu_tools_api_get_amc_intergated() {
        $schemas = $this->getAllSchema();

        $res = array();
        foreach($schemas as $schema=>$connection) {
            $sql = "select '$schema' as schema_name, P.party_name, W.* from web_services_users as W
                  INNER JOIN parties as P ON P.party_id = W.party_id";
            $res = array_merge($res, $this->sqlSchema($schema,$sql)->GetRows());
        }
        $this->buildJSTable(null,$res, array(
            "viewOnly"  => true,
            "excel" => true
        ));

    }

    public function getFieldsHeader($fields = array()) {
    	$res = array();
    	if(empty($fields)) {
    		$fields = $this->getMappingFields();
	    }
    	foreach($fields as $key=>$value) {
    		$res[$key] = $key;
	    }
	    return $res;
    }

    public function mass_create_appraisers()
    {
        $this->buildForm(array(
            $this->buildInput("mass_file", "CSV Mass Users Data", "file"),
            $this->buildInput("mass_geo_file", "CSV Mass GEO Data", "file"),
	        $this->buildInput("mass_broker_company", "CSV Mass Broker Company", "file"),
        ));

        echo "Appraisers Template Columns: <br>
		<b>Column Name ( string, yes/no )</b><br>
		+ username ( required )<br>
		+ class ( AppraiserUser , always required ) <br>
		+ roles ( 10 = appraiser; 12 = company owner ; or both 10,12)<br>
		+ first_name , last_name, ( or using one column 'full_name' ), email, time_zone, 
		company_name, address, city, state, zipcode, office_phone, cell_phone<br>
		mailing_address, mailing_city, mailing_state, mailing_zipcode<br>
		+ ssn_ein<br>
		+ location_ids (1,2,3,4,5 .. etc, put 1 for top node)<br> 

		
		+ panel_assigned, panel_weight, panel_location (number as id , or location name ), panel_preferred  <== require all columns<br>
		+ allowed_loan_types ( don't need this, or specific loan types with commas )<br>
		+ fha (yes|no), license_state, license_level, license_exp, license_number, license_issue_dt <== require all column when doing update or new data<br>
		+ insurance_carrier, insurance_policy, insurance_exp, insurance_limit_total, insurance_effective_date <== don't require all<br> 
		+ monthly_maximum, assignment_threshold, enable_manual_assignment, maximum_property_value  <== don't require all <br>
		
		
		+ locations ( location name, just use top node Name , multiple location by A||B )<br>
		
		<br><br>
		<hr>
		Appraiser Geo Data Template<br>
		+ username ( required )<br>
		+ class ( AppraiserUser ,  required ) <br>
		+ state ( required) <br>
		+ location ( string, as only county name support now )<br>
		+ type ( 'county' for now )<br><br>
        ";

        $mass_broker_company = isset($_FILES['mass_broker_company']) ? $_FILES['mass_broker_company'] : null;
	    if (!empty($mass_broker_company) && $mass_broker_company['tmp_name']!="") {
		    $companies   = $this->CSVToArray( $mass_broker_company['tmp_name']);
		    $this->setupCompany($companies);
		    die("DONE");
	    }

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

    public function getJSONNumberFromArray($array) {
	    $data_string= "";
	    foreach($array as $id) {
	    	if(trim($id)!="") {
			    $data_string .= ',"'.$id.'"';
		    }
	    }
	    return substr($data_string,1);
    }

    var $bad_p1 = "";



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
	        $full_name = $this->getValue("full_name","",$data);
	        if(!empty($full_name)) {
	        	$t = explode(" ",trim($full_name),2);
		        $r['first_name'] = isset($t[0]) ? $t[0] : $r['first_name'];
		        $r['last_name'] = isset($t[1]) ? $t[1] : $r['last_name'];
	        }

            $r['company_name'] = $this->getValue("company_name","",$data);
            $r['address'] = $this->getValue("address","",$data);
            $r['city'] = $this->getValue("city","",$data);
            $r['state'] = $this->getValue("state","",$data);
            $r['zipcode'] = substr($this->getValue("zipcode","",$data),0,5);
            $r['office_phone'] = $this->getValue("office_phone","",$data);
            $r['cell_phone'] = $this->getValue("cell_phone","",$data);

	        $r['middle_initial'] = $this->getValue("middle_initial","",$data);
	        $r['county'] = $this->getValue("county","",$data);
	        $r['mailing_address1'] = $this->getValue("mailing_address","",$data);
	        $r['mailing_address2'] = $this->getValue("mailing_address2","",$data);
	        $r['mailing_city'] = $this->getValue("mailing_city","",$data);
	        $r['mailing_state'] = $this->getValue("mailing_state","",$data);
	        $r['mailing_zipcode'] = substr($this->getValue("mailing_zipcode","",$data),0,5);
	        $r['same_mailing_flag'] = $this->getTrueAsT($r['mailing_address1'] == $r['address'] || empty($r['mailing_address1']));


            $r['class'] = $this->getValue("class","", $data);
            $r['locations'] = $this->getValue("locations","", $data);

            $r['location_ids'] = $this->getValue("location_ids","", $data);
            $r['allowed_loan_types'] = $this->getValue("allowed_loan_types","", $data);

            // vendor setting
	        $r['process_payment'] = $this->getValue("process_payment","", $data);
	        $r['require_review'] = $this->getValue("require_review","", $data);
	        $r['require_qc'] = $this->getValue("require_qc","", $data);
	        $r['enable_atr_generation'] = $this->getValue("enable_atr_generation","", $data);
	        $r['enable_borrower_welcome_email'] = $this->getValue("enable_borrower_welcome_email","", $data);
	        $r['send_borrower_appraisal_report'] = $this->getValue("send_borrower_appraisal_report","", $data);


            $r['class'] = trim($r['class']);
            if($r['class'] == "") {
                echo " NO CLASS <br>";
                return ;
            } else {
                echo $r['class']." => ";
            }
	        $class = trim("Manage".preg_replace("/[^a-zA-Z0-9]+/","",$r['class']));
	        $Appraiser = new $class();
	        $first_name = $r['first_name'];
	        $last_name = $r['last_name'];
	        $company_name = $r['company_name'];
	        $email = $r['contact_email'];
	        $global_user = $this->_getDAO("GlobalUsersDAO")->Execute("SELECT * FROM commondata.global_users where user_name=? ",array($username))->FetchObject();
	        try {
	            if(empty($user->USER_ID) ) {
	                // need to create user
						try {
							$global_user_id = empty($global_user->GLOBAL_USER_ID) ? "null" : '"'.$global_user->GLOBAL_USER_ID.'"';
							echo " creating ... ";
							$p1 = json_encode(array(
								"contact_id"    => null,
								"data"          => array(array(
									"section"   => "contact_info",
									"data"      =>  array(
										"contact_only"  => false,
										"user_name" => $username,
										"first_name"    => $first_name,
										"last_name" => $last_name,
										"email" => $email,
										"company_name"  => $company_name,
										"time_zone" => "-5",
										"login_enabled" => "t",
										"office_phone"  => $r['office_phone'],
										"cell_phone"    => $r['cell_phone'],
										"fax_phone" => "",
										"other_phone"   => "",
										"ssn"       => "",
										"preferred_flag"    => "f",
										"address1"  => $r['address'],
										"address2"  => "",
										"city"  => $r['city'],
										"state" => $r['state'],
										"zipcode"   => $r['zipcode'],
										"zipcode_extension" => ""
									)
								))
							));
							$this->bad_p1 = $p1;
							$this->jsonResult($Appraiser->saveData($p1),$p1);

						} catch(PDOException $e) {
							echo $p1;
							echo " Error On Creating User";
							exit;
						}

	            }
		        $user = $this->_getDAO("UsersDAO")->Execute("SELECT * FROM users where user_name=? ",array($username))->FetchObject();
	            if(!empty($user->USER_ID) && !empty($user->CONTACT_ID) && $r['class'] != "") {
	                $contact_id = $user->CONTACT_ID;
	                echo $contact_id." => ";
	                $user_id = $user->USER_ID;
	                if($r['class'] == "AppraiserUser") {
	                    // check Appraiser Info
	                    $info = new stdClass();
	                    $info->CONTACT_ID = $contact_id;
	                    $line = $this->_getDAO("AppraiserInfoDAO")->get($info);
	                    if(!$line->CONTACT_ID) {
	                        // sert
	                        $this->_getDAO("AppraiserInfoDAO")->Create($info);
	                    }
	                }

	                $roles = $this->getValue("roles","",$data);
	                if(!empty($roles)) {
		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "work_roles",
				                "data"      =>  array(
					                "selected_options"  => explode(",",$roles)
				                )
			                ))
		                ));

		                $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }

	                if(!empty($r['allowed_loan_types'])) {
	                	$p1 = array(
	                		"contact_id"    => $contact_id,
			                "data"          => array(array(
			                	"section"   => "allowed_loan_types",
			                    "data"      =>  array(
			                    	"selected_options"  => explode(",",$r['allowed_loan_types'])
			                    )
			                ))
		                );
	                	$json = json_encode($p1);
		                $this->jsonResult($Appraiser->saveData($json), $json);
		                // $p1 = '{"contact_id":814,"data":{"section":"allowed_loan_types","data":[{"selected_options":["1","2","3","4","5"]}]}}';
	                	// $p1 = '{"contact_id":814,"data":[{"section":"allowed_loan_types","data":{"selected_options":["1","2","3","4","5"]}}]}';

	                }

	                $location_ids = $this->getValue("location_ids","", $data);
	                if(!empty($location_ids)) {
		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "locations",
				                "data"      =>  array(
					                "selected_options"  => explode(",",$location_ids)
				                )
			                ))
		                ));

		                echo " Locations IDS";
		                $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }


	                if($company_name!="") {
	                    // locate company name
		                $sql = "SELECT * FROM companies where company_name=? ";
		                $company_info = $this->query($sql, array($company_name))->fetchObject();
		                if($company_info->COMPANY_ID) {
		                    /*
			                join contacts c on (u.contact_id = c.contact_id)
							join party_contacts pc on (c.contact_id = pc.contact_id)
							join party_companies pco on (pc.party_id = pco.party_id)
							join companies co on (pco.company_id = co.company_id)
		                    */
		                    $party_id = $this->query("SELECT * FROM party_companies WHERE company_id=? ", array($company_info->COMPANY_ID))->fetchObject()->PARTY_ID;
		                    if($party_id) {
		                        $linking = $this->query("SELECT * FROM party_contacts where party_id=? and contact_id=? ", array($party_id,$contact_id))->fetchObject()->CONTACT_ID;
		                        if(!$linking) {
		                            echo " linked $company_name ";
					                $insert_obj = new stdClass();
					                $insert_obj->CONTACT_ID = $contact_id;
					                $insert_obj->PARTY_ID = $party_id;
					                $this->_getDAO("PartyContactsDAO")->Create($insert_obj);
				                }
			                }
		                }
	                }

	                // $license
	                $fha = $this->getTrueAsT($r['fha']);

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
		                    default:
			                    $license_level = 1;
		                    	break;
	                    }
	                }
	                $license_exp = trim($r['license_exp']);
	                if($license_exp != "") {
	                    $license_exp =  @date("Y-m-d", strtotime($license_exp));
	                    if(strpos($license_exp,"1969") !== false) {
		                    $license_exp =  @date("Y-m-d", $r['license_exp']);
	                    }
	                }
	                $license_number = $r['license_number'];









	                if($license_number!="") {
		                $obj = new stdClass();
						$obj->CONTACT_ID = $contact_id;
						$obj->STATE = $license_state;

		                $lic = array(
			                "action"  => "add",
			                "state" => $license_state,
			                "appraiser_license_types_id"    => $license_level,
			                "license_issue_dt"  => $this->getValue("license_issue_dt","",$data),

		                );

						if($r['fha']!="") {
							$obj->FHA_APPROVED_FLAG = $fha;
							$lic['fha_approved_flag'] = $fha;
						}
						if($license_level) {
							$obj->APPRAISER_LICENSE_TYPES_ID = $license_level;
						}
						if($license_number) {
							$obj->LICENSE_NUMBER = $license_number;
							$lic['license_number'] = $license_number;
						}
						if($license_exp) {
							echo " {$license_exp} ";
							$obj->LICENSE_EXP_DT = $license_exp;
							$lic['license_exp_dt'] = $license_exp;
						}

						$license_user_override_flag = $this->getValue("license_user_override_flag","",$data);
		                if(!empty($license_user_override_flag)) {
			                $obj->USER_OVERRIDE_FLAG = $this->getTrueAsT($license_user_override_flag);
			                $lic['user_override_flag'] = $this->getTrueAsT($license_user_override_flag);
		                }

		                $license_eff_dt = $this->getValue("license_eff_dt","",$data);
		                if(!empty($license_eff_dt)) {
			                $obj->LICENSE_EFF_DT = $license_eff_dt;
			                $lic['license_eff_dt'] = $license_eff_dt;
		                }

		                $license_active_flag = $this->getValue("license_active_flag","",$data);
		                if(!empty($license_active_flag)) {
			                $obj->ACTIVE_FLAG =  $this->getTrueAsT($license_active_flag);
			                $lic['active_flag'] = $this->getTrueAsT($license_active_flag);
		                }

						$this->_getDAO("ContactLicenseDAO")->Update($obj);



		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "licenses",
				                "data"      =>  $lic
			                ))
		                ));

		                $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }

	                $maximum_propery_value = $this->getValue("maximum_property_value","", $data);
	                if($maximum_propery_value!="") {
		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "assignment_criteria",
				                "data"      =>  array(
					                "max_appraisal_value"  => $maximum_propery_value,

				                )
			                ))
		                ));
		                $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }

	                $timezone = trim($this->getValue(array("time_zone", "timezone"),"", $data));

	                if($timezone!="") {
	                    $timezone_x = $timezone;
	                    if(!is_numeric($timezone)) {
	                        $time_list = array(
	                            "ALASKA STANDARD TIME" => -9,
	                            "PACIFIC STANDARD TIME" => -8,
	                            "MOUNTAIN STANDARD TIME" => -7,
	                            "CENTRAL STANDARD TIME" => -6,
	                            "EASTERN STANDARD TIME" => -5,
	                        );
	                        $timezone_x = isset($time_list[strtoupper($timezone)]) ? $time_list[strtoupper($timezone)] : null;
	                    }
	                    echo "Timezone {$timezone_x}=";
	                    if(!is_null($timezone_x)) {
	                        $contact_obj = new stdClass();
	                        $contact_obj->CONTACT_ID = $contact_id;
	                        $contact_obj->CONTACT_TIMEZONE = $timezone_x;
	                        $this->_getDAO("ContactsDAO")->Update($contact_obj);
	                        echo "T ";
	                    } else {
	                        echo "F ";
	                    }

	                }

	                $panel_assigned = $this->getValue("panel_assigned","",$data);
	                $panel_weight = $this->getValue("panel_weight","0",$data);
	                $panel_location = $this->getValue("panel_location","",$data);
	                $panal_preferred = $this->getValue("panel_preferred","f",$data);
	                if($panel_assigned!="" && $panel_location!="") {
	                    $panel_assigned = $this->getTrueAsT($panel_assigned);
	                    if(!is_numeric($panel_location)) {
		                    $location_ids = $this->getPartyIDsByLocation($panel_location);
	                    } else {
		                    $location_ids = array($panel_location);
	                    }

	                    $InternalLocationVendorPanels = new ManageInternalLocationVendorPanels();
	                    foreach($location_ids as $location_id) {
		                    $p1 = json_encode(array(
			                    "party_id"    => $location_id,
			                    "data"          => array(array(
				                    "section"   => "location_appraisers",
				                    "data"      =>  array(
					                    "weight"  => $panel_weight,
					                    "preferred" => $this->getTrueAsT($panal_preferred),
					                    "contact_id"    => $contact_id,
					                    "assigned"  => $this->getTrueAsT($panel_assigned)
				                    )
			                    ))
		                    ));

	                        echo " Assign {$location_id} ";
		                    $res = $InternalLocationVendorPanels->saveData($p1);


	                        $this->jsonResult($res, $p1);
	                    }
	                }



	                // insurance
	                $insurance_carrier = $r['insurance_carrier'];
	                $insurance_policy = $r['insurance_policy'];
	                $insurance_exp = $r['insurance_exp'];
	                $insurance_limit_total = $r['insurance_limit_total'];
		            $insurance_limit_per_claim = $this->getValue("insurance_limit_per_claim","", $data);
		            $insurance_effective_date = $this->getValue("insurance_effective_date","",$data);
		            $insurance_data = array();
	                if($insurance_exp != "" ) {
	                    $insurance_exp = @date("Y-m-d", strtotime($insurance_exp));
		                $insurance_data['insurance_exp_dt'] = $insurance_exp;
	                }

	                if($insurance_carrier!="") {
		                $insurance_data['insurance_carrier'] = $insurance_carrier;
	                }

	                if($insurance_policy!="") {
		                $insurance_data['insurance_policy'] = $insurance_policy;
	                }

	                if($insurance_limit_total!="") {
		                $insurance_data['insurance_limit_total'] = $insurance_limit_total;
	                }

		            if($insurance_limit_per_claim!="") {
			            $insurance_data['insurance_limit_per_claim'] = $insurance_limit_per_claim;
		            }

		            if($insurance_effective_date!="") {
			            $insurance_data['insurance_issue_dt'] = $insurance_effective_date;
		            }
		            if(!empty($insurance_data)) {
			            $p1 = json_encode(array(
				            "contact_id"    => $contact_id,
				            "data"          => array(array(
					            "section"   => "insurance",
					            "data"      =>  $insurance_data
				            ))
			            ));
			            $this->jsonResult($Appraiser->saveData($p1), $p1);
		            }


	                if($r['enable_manual_assignment']!="") {
	                    $enable_manual_assignment = $this->getTrueAsT($r['enable_manual_assignment']);
		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "assignment_criteria",
				                "data"      =>  array(
				                	"direct_assign_enabled_flag"    => $enable_manual_assignment
				                )
			                ))
		                ));
	                    $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }


	                $monthly_maximum = $r['monthly_maximum'];
	                if($monthly_maximum!="") {
		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "assignment_criteria",
				                "data"      =>  array(
					                "monthly_max"    => $monthly_maximum
				                )
			                ))
		                ));

	                    $this->jsonResult($Appraiser->saveData($p1), $p1);

	                }


	                $assignment_threshold = $r['assignment_threshold'];
	                if($assignment_threshold!="") {

		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "assignment_criteria",
				                "data"      =>  array(
					                "assignment_threshold"    => $assignment_threshold
				                )
			                ))
		                ));
	                    $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }

	                if($r['locations']!="") {
	                    $location_ids = $this->getPartyIDsByLocation($r['locations'],",");

		                $p1 = json_encode(array(
			                "contact_id"    => $contact_id,
			                "data"          => array(array(
				                "section"   => "locations",
				                "data"      =>  array(
					                "selected_options"    => $location_ids
				                )
			                ))
		                ));
	                    echo " locations  ";
	                    $this->jsonResult($Appraiser->saveData($p1), $p1);
	                }



		            // vendor setting
		            $vendor_setting = array();
		            if(!empty($r['process_payment'])) {
			            $vendor_setting['process_payment'] = $this->getTrueAsT($r['process_payment']);
		            }
		            if(!empty($r['require_review'])) {
			            $vendor_setting['require_review'] = $this->getTrueAsT($r['require_review']);
		            }
		            if(!empty($r['require_qc'])) {
			            $vendor_setting['require_qc'] = $this->getTrueAsT($r['require_qc']);
		            }
		            if(!empty($r['enable_atr_generation'])) {
			            $vendor_setting['enable_atr_generation'] = $this->getTrueAsT($r['enable_atr_generation']);
		            }
		            if(!empty($r['enable_borrower_welcome_email'])) {
			            $vendor_setting['enable_borrower_welcome_email'] = $this->getTrueAsT($r['enable_borrower_welcome_email']);
		            }
		            if(!empty($r['send_borrower_appraisal_report'])) {
			            $vendor_setting['send_borrower_appraisal_report'] = $this->getTrueAsT($r['send_borrower_appraisal_report']);
		            }
		            if(!empty($vendor_setting)) {
			            $p1 = json_encode(array(
				            "contact_id"    => $contact_id,
				            "data"          => array(array(
					            "section"   => "vendor_settings",
					            "data"      =>  $vendor_setting
				            ))
			            ));
			            $this->jsonResult($Appraiser->saveData($p1), $p1);
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

		            $x['middle_initial'] = $r['middle_initial'];
		            $x['county'] = $r['county'];
		            $x['mailing_address1'] = $r['mailing_address1'];
		            $x['mailing_address2'] = $r['mailing_address2'];
		            $x['mailing_city'] = $r['mailing_city'];
		            $x['mailing_state'] = $r['mailing_state'];
		            $x['mailing_zipcode'] = $r['mailing_zipcode'];
		            $x['same_mailing_flag'] = $r['same_mailing_flag'];

	                $update = new stdClass();
	                $update->CONTACT_ID = $contact_id;
	                foreach($x as $key=>$value) {
	                    if($value!="") {
	                        $key = strtoupper($key);
	                        $update->$key = $value;
	                    }
	                }
	                $this->_getDAO("ContactsDAO")->Update($update);

	                $ssn = $this->getValue(array("ssn","ein","appraiser_ein","ssn_ein"),"",$data);
	                if(!empty($ssn)) {
		                $this->_getDAO("AppraiserInfoDAO")->updateSSN($contact_id, trim($ssn));
	                }



	                echo " => Done";
	            } else {
	                echo " => Failed ";
	            }
	        } catch(Exception $e) {
	        	echo " ERRPR ==> COPY THIS ONE AND LET KHOA KNOW: ";
	        	echo $this->bad_p1;


	        }
        } else {
            echo " => Not FOUND ";
        }
        echo "<br>";
    }

    public function jsonResult($result, $p1 = null) {
    	$this->bad_p1 = $p1;
        $x= false;
        foreach($result as $key=>$section) {
            echo " {$key}=";
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
    public function getPartyIDsByLocation($locations, $sep = ",") {
        $locations = explode($sep, $locations);
        $ids = array();
        foreach($locations as $location_name) {
            if(is_numeric($location_name)) {
                $id = $location_name;
            } else {
                $id = $this->getPartyIdByLocation($location_name);
            }

            if(!empty($id)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }



    public function getPartyIdByLocation($location_name) {
        $sql = "SELECT * FROM parties where party_name=? ";
        $party = $this->_getDAO("PartiesDAO")->execute($sql, array($location_name))->fetchObject();
        if($party->PARTY_ID) {
            return $party->PARTY_ID;
        }
        return null;
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
	   $x = preg_replace('/[^\d\.]/', '', "$4.21548");
		echo $x;

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
                    // $this->clear_ucdp_error_process($appraisal_id);
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
            $this->buildInput("appraisal_id","Appraisal IDS (,) or breakline","textarea")
        ));
        $appraisal_id = $this->getValue("appraisal_id");
        if($appraisal_id!="") {
            $list = explode(",",$appraisal_id);
			if(count($list) <= 1) {
				$list = explode("\n",$appraisal_id);
			}
            echo " Press CTRL + S to save all files";
            foreach($list as $appraisal_id) {
                $appraisal_id=trim($appraisal_id);
                if($appraisal_id!="") {
                    echo " <a href='/tandem/download-invoice/?type=a&appraisal_id={$appraisal_id}&filename=/Invoice_{$appraisal_id}.pdf'  title='Invoice_{$appraisal_id}.pdf' >Invoice_{$appraisal_id}.pdf</a> ";
                }
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

	public function menu_tools_utils_check_conditions_function () {
    	$appraisal_id = $this->getValue("appraisal_id","");
    	$this->buildForm(array(
    		$this->buildInput("appraisal_id","Appraisal ID","text", $appraisal_id)
	    ));
    	if(!empty($appraisal_id)) {
    		$this->allConditionsAreCancelledOrSatisfiedWithoutFileId($appraisal_id);
	    }
	}

	function ExecuteDAO($sql, $data = array()) {
    	return $this->query($sql, $data);
	}

	public function allConditionsAreCancelledOrSatisfiedWithoutFileId($AppraisalID) {
		// The method should not affect first completed status
		if($this->_getDAO("AppraisalStatusHistoryDAO")->TimesAppraisalHadThisStatus($AppraisalID,AppraisalStatus::COMPLETED) <= 1) {
			echo "There is only 1 completed. Return FALSE";
			return false;
		}

		// -- GET the Last Condition Status
		$sql = "SELECT *
					FROM appraisal_status_history 
					WHERE status_type_id = ?
					AND appraisal_id = ?
					ORDER BY appraisal_status_history_id DESC
					LIMIT 1";
		$last_condition_status = $this->ExecuteDAO($sql, array(AppraisalStatus::CONDITIONED_ORDER,$AppraisalID))->FetchObject();
		echo "<pre>";
		print_r($last_condition_status);
		echo "</pre>";
		if(!$last_condition_status->APPRAISAL_STATUS_HISTORY_ID) {
			// never go to condition status again
			echo "Never go to Condition Status again. Return FALSE";
			return false;
		}

		// It went to condition again, so look for the status before the condition status
		$sql = "SELECT * FROM appraisal_status_history
 				WHERE appraisal_status_history_id < ".$last_condition_status->APPRAISAL_STATUS_HISTORY_ID."
 				AND appraisal_id = ?
 				AND status_type_id NOT IN (10,13,16)
 				ORDER BY appraisal_status_history_id DESC
				LIMIT 1
		";
		$status_before_condition = $this->ExecuteDAO($sql, array($AppraisalID))->FetchObject();
		Echo "Status Before Condition:";
		echo "<pre>";
		print_r($status_before_condition);
		echo "</pre>";
		// look for all added conditions from the status_before_condition -> STATUS_DATE
		$sql = "SELECT CF.*, C.*
				FROM conditions  AS C	
				LEFT JOIN condition_files AS CF ON (C.condition_id = CF.condition_id)
				WHERE C.create_date >= ?	
					AND C.appraisal_id = ?				
				";
		$rows = $this->ExecuteDAO($sql, array($status_before_condition->STATUS_DATE,$AppraisalID))->GetRows();
		$total_condition = count($rows);
		echo "<pre>";
		print_r($rows);
		echo "</pre>";
		if($total_condition == 0) {
			// no condition to check
			echo " NO CONDITION TO CHECK - RETURN TRUE";
			return true;
		}
		// start checking
		$cancelled = 0;
		$satisfied_without_file = 0;
		foreach($rows as $condition) {
			if($condition['is_cancelled_flag'] == "t") {
				$cancelled++;
			}
			else if($condition['is_satisfied_flag'] == "t" && empty($condition['file_id'])) {
				$satisfied_without_file++;
			}
		}

		echo " Cancelled {$cancelled} / Satisfied without file {$satisfied_without_file} = Total {$total_condition} ";
		if($satisfied_without_file + $cancelled == $total_condition) {
			// cancelled all/some, and satisfied all/some without file
			echo " RETURN TRUE ";
			return true;
		}

		// conditions are not cancelled all, or all/some conditions are satisfied with a file
		echo " RETURN FALSE ";
		return false;
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


	public function menu_users_users_mass_exporting_appraisers() {
		$this->buildForm(array(
			$this->buildInput("type","Export Type","select" , $this->buildSelectOption(array(
								"basic" =>  "Basic Appraiser Info",
								"license"   => "Appraiser License",
								"geopoint"  => "Appraiser GeoPoints"
			))),
			$this->buildInput("list","Only these Appraisers","textarea",$this->getValue("list",""))
		));
		$type = $this->getValue("type","");
		$list = explode("\n",$this->getValue("list",""));
		$original_list = $this->getValue("list","");
		if(empty($list)) {
			$list = explode("\r",$this->getValue("list",""));
			if(empty($list)) {
				$list = explode(",",$this->getValue("list",""));
			}
		}
		if(empty($list) || $original_list === "") {
			$list = array(
				"SELECT * FROM users where user_type=4 "
			);
			$els[0] = array();
		} else {
			foreach($list as $t=>$name) {
				$name = trim($name);
				$list[$t] = "SELECT user_name, user_id, contact_id, user_type FROM users where user_name=? LIMIT 1";
				$els[$t] = array($name);
			}
		}


		if($type !="") {

			$Appraiser = new ManageAppraiserUser();
			$dem =0;
			$filename = "/tmp/output.{$type}.csv";
			$f = fopen($filename,"w+");
			$has_header = false;

			foreach($list as $t=>$sql) {
				$data = $this->query($sql, $els[$t])->getRows();
				foreach ( $data as $user ) {
					echo "Processing {$user['user_name']} <br> ";
					$dem++;
					switch ( $type ) {
						case "basic":

							$row = $this->convertGetDataToSection( $Appraiser->getData( $user['contact_id'] ) );
							$in  = isset( $row['insurance'][0] ) ? $row['insurance'][0] : array(
								"insurance_carrier"         => "",
								"insurance_policy"          => "",
								"insurance_file_id"         => "",
								"insurance_limit_per_claim" => "",
								"insurance_limit_total"     => "",
								"insurance_issue_dt"        => "",
								"insurance_exp_dt"          => "",
							);

							// mapping
							$map = $this->getMappingFields( array(
								"username"       => $row['contact_info']['user_name'],
								"class"          => "AppraiserUser",
								"roles"          => implode( ",", $row['work_roles'] ),
								"first_name"     => $row['contact_info']['first_name'], // or use full_name
								"middle_initial" => $row['contact_info']['middle_initial'],
								"last_name"      => $row['contact_info']['last_name'],

								"email"        => $row['contact_info']['email'],
								"time_zone"    => $row['contact_info']['time_zone'],
								"company_name" => $row['contact_info']['company_name'],
								"address"      => $row['contact_info']['address1'],
								"city"         => $row['contact_info']['city'],
								"state"        => $row['contact_info']['state'],
								"zipcode"      => $row['contact_info']['zipcode'],
								"office_phone" => $row['contact_info']['office_phone'],
								"cell_phone"   => $row['contact_info']['cell_phone'],
								"county"       => $row['contact_info']['county'],

								"mailing_address"           => $row['contact_info']['mailing_address1'],
								"mailing_city"              => $row['contact_info']['mailing_city'],
								"mailing_state"             => $row['contact_info']['mailing_state'],
								"mailing_zipcode"           => $row['contact_info']['mailing_zipcode'],
								"ssn_ein"                   => $row['contact_info']['ssn'],
								// manage locations
								"location_ids"              => implode( ",", $row['locations'] ),

								// panel
								"panel_assigned"            => "", // t f
								"panel_weight"              => "",
								"panel_location"            => "",
								"panel_preferred"           => "", // t f

								// loan types
								"allowed_loan_types"        => "", // or 1,2,3,4,5


								// insurance
								"insurance_carrier"         => $in['insurance_carrier'],
								"insurance_policy"          => $in['insurance_policy'],
								"insurance_exp"             => $this->convertDate( "m/d/Y",$in['insurance_exp_dt']),
								"insurance_limit_total"     => $in['insurance_limit_total'],
								"insurance_limit_per_claim" => $in['insurance_limit_per_claim'],
								"insurance_effective_date"  => $this->convertDate( "m/d/Y",$in['insurance_issue_dt']),

								// assignment
								"monthly_maximum"           => $row['assignment_criteria']['monthly_max'],
								"assignment_threshold"      => $row['assignment_criteria']['assignment_threshold'],
								"enable_manual_assignment"  => $row['assignment_criteria']['direct_assign_enabled_flag'],
								"maximum_property_value"    => $row['assignment_criteria']['max_appraisal_value'],

								// vendor setting
								"process_payment" => $row['vendor_settings']['process_payment'],
								"require_review" => $row['vendor_settings']['require_review'],
								"require_qc"  => $row['vendor_settings']['require_qc'],
								"enable_atr_generation"  => $row['vendor_settings']['enable_atr_generation'],
								"enable_borrower_welcome_email" => $row['vendor_settings']['enable_borrower_welcome_email'],
								"send_borrower_appraisal_report"  => $row['vendor_settings']['send_borrower_appraisal_report'],

								// locations
								// "locations" => implode(",", $row['locations']), // multi A||B
							), true);

							if(!$has_header) {
								$header = $this->getFieldsHeader($map);
								fputcsv($f, $header);
								$has_header = true;
							}
							fputcsv($f, $map);

							break;
						case "license":
							$row = $this->convertGetDataToSection( $Appraiser->getData( $user['contact_id'] ) );
							// mapping
							foreach ( $row['licenses'] as $lic ) {
								$map     = $this->getMappingFields( array(
									"username" => $row['contact_info']['user_name'],
									"class"    => "AppraiserUser",

									"fha"                        => $this->getTrueAsT( $lic['fha_approved_flag'] ),
									"license_state"              => $lic['state'],
									"license_level"              => $lic['license_type'],
									"license_number"             => $lic['license_number'],
									"license_exp"                => $this->convertDate( "m/d/Y", $lic['license_exp_dt'] ),
									"license_eff_dt"             => $this->convertDate( "m/d/Y", $lic['license_eff_dt'] ),
									"license_issue_dt"           => $this->convertDate( "m/d/Y", $lic['license_exp_dt'] ),
									"license_user_override_flag" => $this->getTrueAsT( $lic['user_override_flag'] ),

									"license_active_flag" => $this->getTrueAsT( $lic['active_flag'] ),

								), true );

								if(!$has_header) {
									$header = $this->getFieldsHeader($map);
									fputcsv($f, $header);
									$has_header = true;
								}
								fputcsv($f, $map);
							}

							break;
						case "geopoint":

							$row = $this->convertGetDataToSection( $Appraiser->getData( $user['contact_id'] ) );
							// mapping
							foreach ( $row['geopoints'] as $geo ) {
								$map     = $this->getMappingFields( array(
									"username" => $row['contact_info']['user_name'],
									"class"    => "AppraiserUser",

									"address1" => $geo['address1'],
									"address2" => $geo['address2'],
									"city"     => $geo['city'],
									"state"    => $geo['state'],
									"zipcode"  => $geo['zipcode'],

									"geo_radius"  => $geo['geo_radius'],
									"county_name" => $geo['county_name'],
									"geo_type"    => $geo['geo_type'],

								), true );

								if(!$has_header) {
									$header = $this->getFieldsHeader($map);
									fputcsv($f, $header);
									$has_header = true;
								}
								fputcsv($f, $map);
							}

							break;
					}
				}
				// end for each user
			}

			fclose($f);

			echo "<br>
				CSV Created. Click to download: <a href='?action=downloadFile&file={$filename}'>Appraiser {$type} CSV</a>
				<br>
			";


		}
	}

	public function menu_appraisals_wallet_get_payment_information() {
        $this->buildForm(array(
            $this->buildInput("appraisal_id","Appraisal ID","text")
        ));

        $appraisal_id = $this->getValue("appraisal_id");
        if($appraisal_id != "") {
            $wallet = new Wallet();
            $info = $wallet->getBorrowerWallet($appraisal_id);
            echo "<pre>";
            print_r($info);
            echo "</pre>";
        }
    }


	public function downloadFile($file = null) {
		if(empty($file)) {
			$file = $_REQUEST['file'];
		}
		$file_name = basename($file);
		header('Content-Disposition: attachment; filename="'.$file_name.'"');
		$file_extension = strtolower(substr($file_name, strlen($file_name) - 4));
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Cache-Control: max-age=1, s-maxage=1");
		header('Pragma: public');

		echo file_get_contents($file);
		flush();
		exit;
	}

	public function convertGetDataToSection($getData) {
    	$sections = array();
    	$skip = array(
    		"pricing",
		    "location_pricing"
	    );
    	foreach($getData as $section) {
    		$section_name = $section['section'];
    		if(!in_array($section_name,$skip)) {
    			if(isset($section['data'])) {
				    $sections[$section_name] = $section['data'];
			    } else if(isset($section['options']) && isset($section['selected_options'])) {
				    $sections[$section_name] = $section['selected_options'];
			    }


		    }

	    }
	    return $sections;
	}


    public function menu_tools_api_api_testing() {
    	$sql = "SELECT * FROM web_services_users";
    	echo "
    	API Document: <a href='https://wiki.inhouseusa.com:8444/display/QA/Connexions+API+Testing' target=_blank >https://wiki.inhouseusa.com:8444/display/QA/Connexions+API+Testing</a><br>
    	Link to test: https://{$_SERVER['HTTP_HOST']}/tandem/services/connexionsapi/?login=NAS&token=&method=getAppraisalOrders
    	<br>
    	";
    	$rows = $this->query($sql)->getRows();
    	$this->buildJSTable($this->_getDAO("WebServicesUsersDAO"),$rows, array(
    		"viewOnly" => true
	    ));
    }

    public function menu_users_users_change_user_password() {
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
           $this->_getAppraisalWorkFlowHistory($appraisal_id);
        }
    }

    protected function _getAppraisalWorkFlowHistory($appraisal_id) {
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

    public function get_mapping_type_id($column_name, $table_name = "") {
    	$html = "";
    	switch($column_name) {
		    case "role_type_id":
		    	$html = $this->buildSelectOptionFromDAO("RoleTypesDAO", array("0" => "Everyone"));
		    	break;
		    case "action_id":
			    $html = $this->buildSelectOptionFromDAO("WorkflowActionsDAO");
		    	break;
		    case "workflow_id":
			    $html = $this->buildSelectOptionFromDAO("WorkflowsDAO");
		    	break;
		    case "start_status":
		    case "end_status":
			    $html = $this->buildSelectOptionFromDAO("StatusTypesDAO");
		    	break;
		    case "workflow_condition_id":
			    $html = $this->buildSelectOptionFromDAO("WorkflowConditionsDAO");
		    	break;
	    }
	    return $html;
    }

    public function getColumnsTable() {
    	$table = $this->getValue("table","");
    	$cache_key = "getColumnsTable".$table;
        $html = $this->cacheGet($cache_key);

        if(empty($html)) {
            $columns = $this->getColumnsFromTable($table);
            $html = "";
            $form = array();
            $column_names = array();
            foreach($columns as $c) {
                $column_names[] = $c['column_name'];
                if(strpos($c['column_default'],"::regclass" ) !== false) {
                    $default = "auto_key";
                } else {
                    $default = $c['column_default'];
                }

                $select_data = $this->get_mapping_type_id($c['column_name'], $table);
                if($select_data!="") {
                    $type = "select";
                    $default = $select_data;
                } else {
                    $type = "text";
                }
                $form[] = $this->buildInput($c['column_name'], $c['column_name'] ." ({$c['data_type']}) ", $type ,$default);
            }
            $html = $this->buildForm($form, array("output" => true, "nosubmit" => true));
            $html = str_replace("form","div", $html);
            $this->cacheSet($cache_key, $html, 3600*24*3);
        }

		echo json_encode(array(
			"html" => $html,
            "columns" => $column_names
		));
    }

    public function Execute($User)
    {
	    $action = isset($_GET['action']) ? $_GET['action'] : "";

        if (!in_array(1, $User->Roles)) {
            throw new Exception("You do not have the privilage to access this page", 999);
        }
        try {
            $this->user = $User;
            if(in_array($action, array("JSPost","downloadFile","getColumnsTable","getTablesFromSchema"))) {
                call_user_func(array($this, $action));
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

    public function convertDate($type, $date) {
	    $date = trim($date);
	    $d =  @date("Y-m-d", strtotime($date));
	    if(strpos($d,"1969") !== false) {
		    $d =  @date("Y-m-d", $date);
	    }
	    return $d;
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
                    "update" => 1,
                    "msg"   => "Inserted New record"
                ));
            } catch(Exception $e) {
                print_r($e);
                $this->outputJSON(array(
                    "update" => 3,
                    "msg"   => "Error"
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
                        "update" => 1,
                        "msg"   => "Updated"
                    ));

                }

                if($js_action === "delete") {
                    $this->quickBackup("SELECT * FROM {$table} WHERE {$primary_key}=? ",array($primary_id));
                    $sql = "DELETE FROM {$table} WHERE {$primary_key}=? ";

                    $update[] = $primary_id;
                    $this->query($sql,$update);

                    $this->outputJSON(array(
                        "update" => 1,
	                    "msg"   => "Deleted"
                    ));
                }





            } catch(Exception $e) {
                print_r($e);
                $this->outputJSON(array(
                    "update" => 3,
                    "msg"   => "Error"
                ));
            }

        }
        $this->outputJSON(array(
            "update" => 2,
            "msg"   => "data failed {$table} {$primary_id} - {$primary_key} - {$js_action}",
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

    public function search_users_by_roles() {

	    $this->buildForm(array(
		    $this->buildInput("roles","Roles (,)","text"),
	    ));



    }

    public function update_user_global() {
        $path = "/var/www/tandem.inhouse-solutions.com/scripts";
        $file_input = $path."/internal_user.csv";
        $script = $path."/addUsersToSite.php";
        $schemas = $this->getAllSchema();
        $site_list = array("all" => "all");
        foreach($schemas as $schema=>$connection) {
            $site_list[$schema]=$schema;
        }
        $this->buildForm(array(
            $this->buildInput("username","Username (username)","text"),
            $this->buildInput("email","Email (email)","text"),
            $this->buildInput("first_name","First Name (first_name) ","text"),
            $this->buildInput("last_name","Last Name (last_name)","text"),
            $this->buildInput("user_type","User Type (user_type , 1= lender, 6 =cx)","text", 1),
            $this->buildInput("roles","Roles (roles , look up table below )","text", "1, 2"),
            $this->buildInput("parties","Parties ( parties = 1, or blank for vendor) ","text", "1"),
            $this->buildInput("office_phone","Office Phone ( office_phone or blank) ","text", ""),
            $this->buildInput("time_zone","Time Zone (time_zone = -5 or text )","select", $this->buildSelectOption(array(
                "-9"    =>  "Alaska Standard Time",
                "-8"    =>  "Pacific Standard Time",
                "-7"    => "Mountain Standard Time",
                "-6"    => "Central Standard Time",
                "-5"    => "Eastern Standard Time",
            ))),
            $this->buildInput("site","Site (site = all , or schema name )","select", $this->buildSelectOption($site_list)),
            $this->buildInput("reset_roles","Reset Roles (reset_roles, t or f)","select", $this->buildSelectOption(array("f"=>"No","t"=>"Yes"))),
            $this->buildInput("reset_contact","Reset Contact ( reset_contact, t or f)","select", $this->buildSelectOption(array("f"=>"No","t"=>"Yes"))),
            $this->buildInput("deactivate","Deactivate ( deactivate, t or f )","select", $this->buildSelectOption(array("f"=>"No","t"=>"Yes"))),
            $this->buildInput("mass_users_file","Mass CSV File Users","file"),
	        $this->buildInput("mass_change_password","Mass CSV File Change Password","file"),
        ));



        $username = $this->getValue("username","");
        $first_name = $this->getValue("first_name","");
        $last_name = $this->getValue("last_name","");

        $file_upload = isset($_FILES['mass_users_file']) ? $_FILES['mass_users_file'] : null;
        if(!empty($file_upload) && trim($file_upload['tmp_name'])!=="") {
            echo "COPY ".$file_upload['tmp_name']." <br>";
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

	    $mass_change_password = isset($_FILES['mass_change_password']) ? $_FILES['mass_change_password'] : null;
	    if(!empty($mass_change_password) && trim($mass_change_password['tmp_name'])!=="") {
		    $data = $this->CSVToArray($mass_change_password['tmp_name']);
		    foreach($data as $row) {
			    $username = $row['username'];
			    $password = $row['password'];
			    $this->print_out($username." ".$password);
			    $ID = $this->_getDAO("GlobalUsersDAO")->GetUserId($username);
			    $this->_getDAO("GlobalUsersDAO")->UpdatePassword($ID,$password);
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
                "phone" => $this->getValue("phone"),
                "time_zone" => $this->getValue("time_zone"),
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
            $this->buildInput("file_upload","File Upload","file"),
            $this->buildInput("options","Extra options", "select",$this->buildSelectOption(array(
                "check" => "Check Information Only",
                "change"    => "Change Username",
                "move"      => "Move Orders Betweens"
            )))
        ), array(
            "confirm"   => true
        ));

        $mass_upload = $_FILES['file_upload'];
        $mass = false;
        if($mass_upload['tmp_name']!="") {
            $data = $this->CSVToArray($mass_upload['tmp_name']);
            $mass = true;
        } else {
            $data = array(
                0 => array(
                    "username"  => $username,
                    "new_username"  => $new_username
                )
            );
        }
        foreach($data as $row) {
            $username = $row['username'];
            $new_username = $row['new_username'];
            echo $username." => ".$new_username." <br>";
            if($username!="" || $new_username !="") {
                $UsersDAO = $this->_getDAO("UsersDAO");
                $current_user_data = $UsersDAO->Execute("SELECT * FROM users where user_name=? ", array($username))->getRows();
                if(!$mass)  $this->buildJSTable($UsersDAO, $current_user_data);
                $current_user = isset($current_user_data[0]) ? $current_user_data[0] : array();

                $new_user_data = $UsersDAO->Execute("SELECT * FROM users where user_name=? ", array($new_username))->getRows();
                if(!$mass)  $this->buildJSTable($UsersDAO, $new_user_data);
                $new_user = isset($new_user_data[0]) ? $new_user_data[0] : array();

                if($options == "check" ) {
                    if(!$mass)  $this->h4("Check Orders {$username}");
                    $orders = $this->query("select appraisal_id, appraiser_id, requested_by from appraisals where requested_by=? OR appraiser_id=? ", array($current_user['user_id'], $current_user['contact_id']))->getRows();

                    if(!$mass) $this->buildJSTable($this->_getDAO("AppraisalsDAO"),
                        $orders
                    );
                }

                if($options == "change" && !empty($new_username) && !empty($username)) {
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
            $this->getAppraisalProducts($appraisal_id);
	        $this->buildJSTable($this->_getDAO("AppraisalProductsDAO"),$this->_getDAO("AppraisalProductsDAO")->Execute("select appraisal_product_id, appraisal_product_name from appraisal_products where enabled_flag=true")->GetRows(), array("viewOnly"=>true , "excel"=>true));

        }
    }

    public function getAppraisalProducts($appraisal_id) {
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
        $tables_list = $this->getTablesFromSchema(false);
        $tables = array();
        foreach($tables_list as $row) {
            $tables[$row['table_name']] = $row['table_name'];
        }
        $this->buildForm(array(
            $this->buildInput("table_keyword","Table Name","text","", array(
                "onchange"  => "buildColumns(this.value)"
            )),
            $this->buildInput("table_select","OR Select Table","select", $this->buildSelectOption(array_merge(array(
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
                "________"   => "---",
            ), $tables)), array(
                "onchange"  => "buildColumns(this.value)"
            )),
            $this->buildInput("column_lookup","Search Column Name","select",""),
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
            $this->buildInput("limit","Limit Result","text","100")
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
            $this->buildJSTable($dao,$data, array(
                "excel" => true
            ));
        }
    }

    protected function _getAllLocations($return_all_info = true) {
        $obj = new stdClass();
        $obj->PARTY_ID = 1;
        $locations = $this->_getDAO("PartyHierarchyDAO")->GetDecendants($obj);
        $locations[]=1;
        return $locations;
    }

    public function menu_tools_products_set_product_available() {
        $this->buildForm(array(
            $this->buildInput("input_file","CSV ( id, name, party=number or all )","file"),
            $this->buildInput("actionx","Action","select", $this->buildSelectOption(array(
                "---"   => "-----",
                "clear" => "Reset Products Available Table"
            )))
        ), array(
            "confirm"   => true
        ));


        $products = isset($_FILES['input_file']) ? $_FILES['input_file'] : null;
        $parties = $this->_getAllLocations(false);
       // print_r($parties);
        $actionx = $this->getValue("actionx","");
        if (!empty($products) && $products['tmp_name']!="") {
            $csv = $this->CSVToArray($products['tmp_name'], true, true);
            if($actionx == "clear") {
                $sql = "DELETE FROM appraisal_product_availability";
                $this->query($sql);
            }
            foreach($csv as $item) {
                $id = $item['id'];
                $name = $item['name'];
                $party = $item['party'];
                if(trim($party) == 'all') {
                    $x = $parties;
                } else {
                    $x = array($party);
                }
                if(empty($id) && !empty($name)) {
                    // find id by name
                    $sql = "SELECT * FROM appraisal_products where appraisal_product_name=? LIMIT 1";
                    $t = $this->query($sql, array($name))->FetchObject();
                    $id = $t->APPRAISAL_PRODUCT_ID;
                }

                // clear old data

                foreach($x as $party_id) {

                    $p1 = json_encode(array(
                        "party_id"    => $party_id,
                        "data"          => array(array(
                            "section"   => "location_product_availability",
                            "data"      =>  array(
                                "appraisal_product_availability_id"    => "",
                                "party_id"  => $party_id,
                                "appraisal_product_id" => $id,
                                "checked"   => true
                            )
                        ))
                    ));

                    echo " {$id} => $party_id  ";
                    $Location = new ManageInternalLocation();
                    $r = $this->jsonResult($Location->saveData($p1), $p1);
                    echo "<br>";
                }


            }
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
        	if(is_numeric($key)) {
        		$x = "{$key} - ";
	        } else {
        		$x = "";
	        }
            $html .= "<option value='{$key}'> {$x} {$value} </option>";
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
            $this->_getAppraisalEmail($appraisal_id, $message_to);
        }

    }

    public function _getAppraisalEmail($appraisal_id, $message_to = "") {
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

        $sql = "SELECT A.appraisal_id, B.notification_job_id, B.job_completed_flag, B.subject, 
B.body,  B.message_to , B.message_from, B.last_attempted_timestamp, E.event_date, B.bounce_flag , B.bounce_reason 
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

    public function _buildInputAttr($options) {
        $str = "";
        foreach($options as $k=>$v) {
            if(is_string($v)){
                $str .= ' '.$k.'="'.$v.'" ';
            }
        }
        return $str;
    }
    public function buildInput($id, $label, $type, $default = "", $options = array()) {
        $html = "<tr><td>{$label}:</td><td>";
        $r = $this->getValue($id,$default);
        switch ($type) {
            case "select":
                $default = str_replace("'{$r}'", "'{$r}' selected",$default);
                if(trim($default) == '') {
                    $default = "<option value=$r >$r</option>";
                }
                $html .= "<select ".$this->_buildInputAttr($options)." data-auto-input='t' class='auto-input-g'  name={$id} id={$id} >{$default}</select>";

                break;
            case "file" :
                $html .=  " <input ".$this->_buildInputAttr($options)."  data-auto-input='t'  class='auto-input-g'  type=file name={$id} id={$id} > ";
                break;
            case "textarea":
                $html .=  " <textarea  ".$this->_buildInputAttr($options)." data-auto-input='t'  class='auto-input-g'  style='width: 700px;height:300px;' name={$id} id={$id} >{$default}</textarea> ";
                break;
            case "text":
            default:
                $html .=  " <input  ".$this->_buildInputAttr($options)." data-auto-input='t'  class='auto-input-g'  type=text name={$id} id={$id} value='{$r}' > ";
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
            $this->buildInput("config_key_name","OR Config Key Name",""),
	        $this->buildInput("func","OR Select View","select", $this->buildSelectOption(array("---","Recent Config Changes","Recent Users Changes")))
        ));
        $keywords = $this->getValue("keywords","");
        $config_key_sort_name = $this->getValue("config_key_short_name","");
        $config_key_name = $this->getValue("config_key_name","");
        $func = $this->getValue("func",0);
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

	    if($func == 1) {
        	$keywords = "CONFIG_KEY_ID";
	    }
	    if($func == 2) {
		    $keywords = "CONTACT_ID";
	    }

        if($keywords!="") {
            $sql = "SELECT * FROM changes_log where (new_data like ? OR old_data like ?) 
            order by log_id DESC limit 100";
            echo $sql;
            $ChangesLogDAO = $this->_getDAO("ChangesLogDAO");
            $this->buildJSTable($ChangesLogDAO, $ChangesLogDAO->Execute($sql,
                array("%{$keywords}%", "%{$keywords}"))->GetRows(), array("translate_log" => true));
            if($keyword2!='') {
                $sql = "SELECT * FROM changes_log where (new_data like ? OR old_data like ?) 
                order by log_id DESC limit 100";
                $ChangesLogDAO = $this->_getDAO("ChangesLogDAO");
                $this->buildJSTable($ChangesLogDAO, $ChangesLogDAO->Execute($sql,
                    array("%{$keyword2}%", "%{$keyword2}"))->GetRows(), array("translate_log" => true));
            }
        }
    }

    public function buildForm($data = array(), $options = array()) {
    	if(!empty($this->argv)) {
    		return;
	    }
        $action = isset($options['action']) ? $options['action'] : $_GET['action'];
        $confirm = isset($options['confirm']) ?  "confirm('Are you sure?')" : "true";
	    $attrs = "";
	    foreach($options as $key=>$v) {
			$attrs = " data-$key='{$v}' ";
	    }
        $html = "<form action='?action={$action}' {$attrs} method=post enctype='multipart/form-data' onsubmit=\"return {$confirm};\" ><table >";
        foreach($data as $input) {
            $html .= "<div >
                       {$input}
                </div> ";
        }
	    $submit = "<input type='submit' value='Submit'>";

	    $nosubmit = isset($options['nosubmit']) ? $options['nosubmit'] : false;
	    if($nosubmit) {
	    	$submit = "";
	    }
        $html .= "</table><br> {$submit}</form>";
		$return = isset($options['output']) ? $options['output'] : false;
		if($return) {
			return $html;
		} else {
			echo $html;
		}

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
		$excel = array();
		$excel_header = array();
		$excel_data = array();
        foreach ($data as $row) {
            $tmp++;
            $color = $tmp % 2 == 0 ? "green" : "pink";
            $tbody .= "<tr class='bh_{$color}'>";
            $row_id = "";
            $c=0;
            $excel_row = array();
            foreach($row as $col=>$value) {
	            $c++;
                if($tmp == 1) {
	                if($c == 3) {
		                if(isset($options['translate_log'])) {
			                $header .= "<th> Extra Log </th>";
		                }
	                }

                    $header .= "<th data-name='{$col}'>{$col}</th>";
	                $excel_header[] = $col;
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

                if($c == 3) {
                    // addon
	                if(isset($options['translate_log'])) {
	                	$info = $row['new_data'];
		                $log_result = "";
		                preg_match("/CONFIG_KEY_ID\":([0-9\"]+)/",$info,$mm);
		                if(isset($mm[1])) {
			                $config_key_id = preg_replace("/[^0-9]+/","",$mm[1]);
			                $search_obj = new stdClass();
			                $search_obj->CONFIG_KEY_ID = $config_key_id;
			                $config_obj = $this->_getDAO("ConfigKeysDAO")->Get($search_obj);
			                $log_result .= $config_obj->CONFIG_KEY_NAME;
		                }

	                	$tbody .= "<td> $log_result </td>";
	                }
                }
	            $excel_row[] = $value;
                $tbody .= "<td vaign='top' data-primary-value='{$row_id}' data-table='{$table}' data-primary-key='{$primary_key}' data-name='{$col}' style='{$width}'>                                
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
                if(isset($options['hookData']) && isset($options['hookData'][$col])) {
                    if(isset($options['hookData'][$col]['table']) && isset($options['hookData'][$col]['display'])) {
                        $_column = !isset($options['hookData'][$col]['column']) ? $col : $options['hookData'][$col]['column'];
                        if(!is_array($options['hookData'][$col]['display'])) {
                            $options['hookData'][$col]['display'] = array($options['hookData'][$col]['display']);
                        }
                        $_x_select = implode(", ",$options['hookData'][$col]['display']);
                        $sub_set = "SELECT {$_x_select} FROM {$options['hookData'][$col]['table']} WHERE {$_column}=? limit 1";
                        $_tmp_data = $this->query($sub_set, array($value))->getRows();

                        if(count($_tmp_data) > 0) {
                            $tbody .= "<div style=display:inline;font-size:11px;>";
                            $tu = array();
                            foreach($_tmp_data[0] as $_column=>$_v) {
                                $tu[] = $_v;

                            }
                            $tbody .= implode(", ", $tu);
                            $tbody .= "</div>";
                        }

                    }
                    else if(isset($options['hookData'][$col][$value])) {
                        $tbody .= "<div style=display:inline;font-size:11px;>{$options['hookData'][$col][$value]}</div>";
                    }

                }
                $tbody .= "
                           </td>";
            }
            $excel_data[] = $excel_row;
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
        $sql_table = "<textarea id={$data_sql_id} class={$data_sql_id} data-sql='1' style=width:100%;height:50px;display:none; ></textarea><br>
                            <button data-sql='$data_sql_id'  data-table='$table' onclick='$(\".{$data_sql_id}\").toggle();'>SQL Box</button> 
                            <button data-sql='$data_sql_id' class={$data_sql_id}  data-table='$table' style=display:none; onclick='run_custom_sql(this);'>Run SQL</button> 
                            <button data-sql='$data_sql_id' class={$data_sql_id}  data-table='$table' style=display:none; data-cols='".implode(", ",$cols)."' onclick='add_insert_into(this)'> Add INSERT INTO </button>
                            <button data-sql='$data_sql_id' class={$data_sql_id}  data-table='$table' style=display:none; data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='add_delete_from(this)'> Add DELETE FROM </button>
                            <button data-sql='$data_sql_id' class={$data_sql_id}  data-table='$table' style=display:none; data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='add_update_from(this)'> Add Update </button> 
                            <button data-sql='$data_sql_id' class={$data_sql_id}  data-table='$table' style=display:none; data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='build_insert_from(this)'> Build Insert Form </button> 
                      <div id='form-{$data_sql_id}' style='display: none;margin:20px;'>
                      	<div class='my_form'>
                      	</div>
                      	<div>
                      		<button data-sql='$data_sql_id' data-table='$table' data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='create_insert_sql(this)'> Create Insert SQL </button> 
                      		<button data-sql='$data_sql_id' data-table='$table' data-col-1='{$cols[0]}'  data-col-2='{$cols[1]}' onclick='cancel_sql_form(this)'> Close </button> 
                      	</div>
                      </div>
                      
</div>
                            ";
        if(isset($options['viewOnly'])) {
            $sql_table = "";
        }
        $table = $sql_table."<table class=table width='100%'><thead>{$header}<th></th></thead><tbody>{$tbody}</tbody></table>";

        if(isset($options['excel'])) {
        	$excel[] = $excel_header;
        	foreach($excel_data as $line) {
        		$excel[] = $line;
	        }
	        $table = " <form method='post'>	<input type='submit' value='Export To Excel'> <textarea name='json_excel' style='position: absolute;left:-1000px;top:-1000px;'>".json_encode($excel)."</textarea></form><br>".$table;
        }


        echo $table;
    }

    public function getValue($name, $default = "", $data = array()) {
    	if(empty($data) && isset($this->argv[2])) {
    		$data = $this->getCmdVars();
	    }
        if(!is_array($name)) {
            $name_list = array($name);
        } else {
            $name_list = $name;
        }
        $found = null;
        foreach($name_list as $name) {
            if(!empty($data) && isset($data[$name])) {
                $found =  is_string($data[$name]) || is_numeric($data[$name]) ? trim($data[$name]): $data[$name];
            }
            elseif(isset($_POST[$name])) {
                $found = is_string($_POST[$name]) || is_numeric($_POST[$name]) ? trim($_POST[$name]) : $_POST[$name];
            }
            elseif(isset($_REQUEST[$name])) {
	            $found = is_string($_REQUEST[$name]) || is_numeric($_REQUEST[$name]) ? trim($_REQUEST[ $name ]) : $_REQUEST[ $name ];
            }
        }

        if(is_null($found)) {
            $found = is_string($default) || is_numeric($default) ? trim($default) : $default;
        }
        return $found;

    }

    public function menu_tools_delpoyment_update_support_tools() {
        $filename = '/var/www/tandem.inhouse-solutions.com/includes/pages/specials/AdminSupport.php';
        echo "Original File" . date ("F d Y H:i:s.", filemtime($filename));
        echo " ";
        echo filesize($filename)/1024;
        exec('wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/blankFile.txt -O /var/www/tandem.inhouse-solutions.com/scripts/internal_user.csv');
        exec('wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/AdminSupport.php -O /var/www/tandem.inhouse-solutions.com/includes/pages/specials/AdminSupport.php');
        exec('wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/blankFile.txt -O /var/www/tandem.inhouse-solutions.com/scripts/internal_user.csv');
        exec('wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/AdminSupport.php?'.rand(1,9999).' -O /var/www/tandem.inhouse-solutions.com/includes/pages/specials/AdminSupport.php');
        exec('wget --no-cache https://raw.githubusercontent.com/khoaofgod/AdminSupport/master/addUsersToSite.php -O /var/www/tandem.inhouse-solutions.com/scripts/addUsersToSite.php');


        echo "<br><br> Updated File" . date ("F d Y H:i:s.", filemtime($filename));
        echo filesize($filename)/1024;
        $this->cacheDelete("md1");
        $this->cacheDelete("md2");
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
                        WHERE (table_schema = ?
                        and table_name = ?) 
                        or (table_schema = 'commondata'
                        and table_name = ?) ";
        $columns = $this->_getDAO("AppraisalsDAO")->Execute($sql, array($info['PG_SQL']['USER'], $table, $table))->GetRows();
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

	public function menu_tools_utils_fix_appraiser_null_geo() {
		$this->buildForm(array(
			$this->buildInput("menu_action","Select Action", "select", $this->buildSelectOption(array(
				0 => "---Select Action---",
				1 => "Fix Issue with Appraisers GEO"
			)))
		));
		$menu = $this->getValue("menu_action",0);

		if($menu == 1) {
			$sql  = "SELECT * FROM contact_addresses where geo_type='radius' and latitude is NULL ";
			$rows = $this->query( $sql )->GetRows();
			foreach($rows as $row) {
				$contact_address_id = $row['contact_address_id'];
				$GeoCodeService = new GeoCodeService();
				$res = $GeoCodeService->GetGeo($row['address1']." ".$row['city']." ".$row['state']." ".$row['zipcode']);
				if(!empty($res->LONGITUDE)) {
					$new_std = new stdClass();
					$new_std->CONTACT_ADDRESS_ID = $contact_address_id;
					$new_std->LONGITUDE = $res->LONGITUDE;
					$new_std->LATITUDE = $res->LATITUDE;
					$this->_getDAO("ContactAddressesDAO")->update($new_std);
				} else {
					echo " ADDRESS {$contact_address_id} IS BAD GEO <br>";
				}

			}

            $sql  = "SELECT * FROM contacts where long is NULL and contact_type=4 ";
            $rows = $this->query( $sql )->GetRows();
            foreach($rows as $row) {
                $contact_id = $row['contact_id'];
                $GeoCodeService = new GeoCodeService();
                $res = $GeoCodeService->GetGeo($row['address1']." ".$row['city']." ".$row['state']." ".$row['zipcode']);
                if(!empty($res->LONGITUDE)) {
                    $new_std = new stdClass();
                    $new_std->CONTACT_ID = $contact_id;
                    $new_std->LONG = $res->LONGITUDE;
                    $new_std->LAT = $res->LATITUDE;
                    $this->_getDAO("ContactsDAO")->update($new_std);
                } else {
                    echo " CONTACT ID {$contact_id} IS BAD GEO <br>";
                }

            }

		}
		$sql  = "SELECT * FROM contact_addresses where geo_type='radius' and latitude is NULL ";
		$rows = $this->query( $sql )->GetRows();
		$this->buildJSTable($this->_getDAO("ContactAddressesDAO"), $rows, array(
			"viewOnly" => true
		));

	}

    public function menu_tools_utils_clean_duplicated_addresses() {
    	try {
		    $sql  = "SELECT * FROM contact_addresses ";
		    $rows = $this->query( $sql )->GetRows();
		    $ex = array();
		    foreach ( $rows as $row ) {
		    	//echo "{$row['contact_id']} ";
			    if(isset($ex[$row['contact_address_id']])) {
			    	continue;
			    }
			    $data = array(
				    $row['contact_id'],
				    $row['contact_address_id']
			    );
			    $extra_where = "";
			    switch ($row['geo_type']) {
				    case "county":
					    $extra_where .= " AND state=? AND county_name=? ";
						$data[] = $row['state'];
					    $data[] = $row['county_name'];
			            break;

			    }
			    if($extra_where == "") {
			    	continue;
			    }
			    $sql = "SELECT * FROM contact_addresses
					WHERE contact_id=?				  
					AND contact_address_id > ?
					{$extra_where} ";
			    $r   = $this->query( $sql, $data)->GetRows();
			  //  echo "  | {$row['contact_address_id']} | Found: ".count($r);
			    foreach ( $r as $line ) {
				    $sql = "DELETE FROM contact_addresses WHERE contact_address_id=? ";
				    $this->query( $sql, array( $line['contact_address_id'] ) );
				    $ex[$line['contact_address_id']] = true;
			    }
			 //   echo " <br>";
		    }
		    $sql = "DELETE FROM contact_addresses WHERE geo_type='county' AND county_name <> '' AND (state='' OR state IS NULL);";
		    $this->query( $sql);
		    echo "DONE";
	    } catch(Exception $e) {
    		echo "<pre>";
    		print_r($e);
    		echo "</pre>";
	    }


    }

    public function buildAdminMenu($name, $return=true) {
		$methods = get_class_methods($this);
		$groups = array();
		foreach($methods as $method) {
			$tmp = explode("_",$method,4);
			if($tmp[0] === "menu" && count($tmp) === 4 && $tmp[1] === $name) {
				// menu structure
				$group = $tmp[2];
				$groups[$group][] = array(
					"name" => trim(ucwords(str_replace("_", " ",$tmp[3]))),
					"action"     => $method
				);
			}
		}

		$html = '';

		$dem=0;
		foreach($groups as $group=>$data) {
			foreach($data as $menu) {
				$html .= '<li><a href="?action='.$menu['action'].'">'.$menu['name'].'</a></li>';
			}
			$dem++;
			if($dem < count($groups)) {
				$html.= '<li role="separator" class="divider"></li>  ';
			}
		}

		if(!$return) {
			echo $html;
		} else {
			return $html;
		}
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

function buildColumns(table_name) {
    var x = $("#column_lookup");
    x.html("");
    $.get("?action=getColumnsTable&table=" + table_name, function($json) {
         $.each($json.columns, function(i, column_name) {
             if(column_name == "appraisal_id") {
                  x.prepend("<option value=" + column_name + " selected >" + column_name + "</option>");
             } else {
                  x.append("<option value=" + column_name + ">" + column_name + "</option>");
             }
            
         });
    });
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
            } else {
                
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
                alert($json.msg);
         });
    }
   
} 

function build_insert_from(obj) {
     var table =  $(obj).attr("data-table");
     var id = $(obj).attr("data-sql");
     var form_id = $("#form-" + id).find(".my_form:first");
    $.post("?action=getColumnsTable", {
             "table": table
         }, function($json) {
        	$(form_id).html($json.html);
        	$("#form-" + id).show();
     });
}

function create_insert_sql(obj) {
     var table =  $(obj).attr("data-table");
     var id = $(obj).attr("data-sql");
     var form_id = $("#form-" + id).find(".my_form:first");
     var columns = "";
     var data = "";
     var v = "";
     $.each(form_id.find("[data-auto-input=\'t\']"), function($i, $input){
          v = $($input).val();
          if(v !== "auto_key" && v!=="") {
                columns += ", " + $($input).attr("name");
          		data += ",\'" + $($input).val() + "\' ";
          }
        
	 });
     columns = columns.substr(1);
     data = data.substr(1);
     var sql = "INSERT INTO " + table + " (" + columns + ") VALUES (" + data + ")";
     $("#" + id).html(sql);
}

function cancel_sql_form(obj) {
     var table =  $(obj).attr("data-table");
     var id = $(obj).attr("data-sql");
     var form_id = $("#form-" + id);
     $(form_id).hide();
}

function pullorder() {
    var id = $("#top_value_search").val();
    window.location = "?action=menu_appraisals_order_pull_order&appraisal_id=" + id;
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
                      '.$this->buildAdminMenu("appraisals").'
          </ul>
        </li>
              
        <li class="dropdown"><a href="#"  class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" 
        aria-expanded="false">Workflows & Queues <span class="caret"></span></a>
               <ul class="dropdown-menu">
                    <li><a href="?action=workflows">Workflows Lookup</a></li>
                    <li><a href="?action=appraisal_workflows_history">Appraisal Workflows History</a></li>
                    '.$this->buildAdminMenu("workflows").'   
                     <li role="separator" class="divider"></li> 
                    '.$this->buildAdminMenu("workqueues").'          
              </ul>
        </li>
        <li class="dropdown"><a href="#"  class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">User Account <span class="caret"></span></a>
               <ul class="dropdown-menu">
                <li><a href="?action=remove_users">Deactivate Users</a></li>                                                                     
                <li><a href="?action=change_username">Change Username</a></li>      
                  <!-- <li><a href="?action=login_as_user">Login as User</a></li> -->      
                 <li role="separator" class="divider"></li>  
                <li><a href="?action=update_user_global">Mass Update Users Global</a></li>  
                <li><a href="?action=mass_create_appraisers">Mass Create Appraisers</a></li>    
                <li><a href="?action=mass_sending_email">Mass Sending Emails</a></li>               
                <li><a href="?action=searchUsers">Search & Export Users</a></li>
                 '.$this->buildAdminMenu("users").'
              </ul>
        </li>
       
    	<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Settings <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="?action=change_location_parent">Change Location Parents</a></li>
            <li><a href="?action=changes_log">Search Changes Log</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="?action=enable_products">Enable Products</a></li>
            <li><a href="?action=generateInvoice">Mass Gen Invoices</a></li>
          '.$this->buildAdminMenu("settings").'                
          </ul>
        </li>   
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Tools & Utils <span class="caret"></span></a>
          <ul class="dropdown-menu">            
          	'.$this->buildAdminMenu("tools").'                
          </ul>
        </li>        
      </ul>
      <form class="navbar-form navbar-left" onsubmit="top_bar_submit();return false;">
        <div class="form-group">
          <input type="text" class="form-control" id="top_value_search" placeholder="Appraisal ID">
        </div>
        <button type="button"  class="btn btn-default" onclick="pullorder();">Pull Order</button>
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
            <li><a>Server Name: '.gethostname().'</a></li>
          </ul>
        </li>        
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

        ';
        $this->needUpdateSystem();
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
    private $debug=true;
    private $file_id;
    private $location_config;
    private $web_service_user;

    private function debug($message, $title='') {
    	if(!is_array($message)) {
    		if($message === true) {
			    echo "TRUE : ". $title."<br>";
		    } else {
			    echo $message." : ". $title."<br>";
		    }

	    } else {
    		echo $title.": <br>";
    		echo "<pre>";
    		print_r($message);
    		echo "</pre>";
	    }

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

class NiceSSH {
	// SSH Host
	private $ssh_host = 'myserver.example.com';
	// SSH Port
	private $ssh_port = 22;
	// SSH Server Fingerprint
	private $ssh_server_fp = 'xxxxxxxxxxxxxxxxxxxxxx';
	// SSH Username
	private $ssh_auth_user = 'username';
	// SSH Public Key File
	private $ssh_auth_pub = '/home/username/.ssh/id_rsa.pub';
	// SSH Private Key File
	private $ssh_auth_priv = '/home/username/.ssh/id_rsa';
	// SSH Private Key Passphrase (null == no passphrase)
	private $ssh_auth_pass;
	// SSH Connection
	private $connection;



	public function connect($ssh_host, $user, $pass) {
		$this->ssh_host = $ssh_host;
		$this->ssh_auth_user = $user;
		$this->ssh_auth_pass = $pass;
		if (!($this->connection = ssh2_connect($this->ssh_host, $this->ssh_port))) {
			throw new Exception('Cannot connect to server');
		}
		if (!ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass)) {
			throw new Exception('Autentication rejected by server');
		} else {
			echo " <br> Connected {$ssh_host} <br>";
		}
	}
	public function exec($cmd) {
		if (!($stream = ssh2_exec($this->connection, $cmd))) {
			throw new Exception('SSH command failed');
		}
		stream_set_blocking($stream, true);
		$data = "";
		while ($buf = fread($stream, 4096)) {
			$data .= $buf;
		}
		fclose($stream);
		return $data;
	}
	public function disconnect() {
		$this->exec('echo "EXITING" && exit;');
		$this->connection = null;
	}
	public function __destruct() {
		$this->disconnect();
	}
}

if(isset($argv)) {
	// command line
	$class = new specials_AdminSupport();
	$function = $argv[1];
	$class->argv = $argv;
	$class->$function();
}


if(isset($_POST['json_excel'])) {
	$filename = "export.".@date("U").".csv";

	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Pragma: no-cache");
	header("Expires: 0");

	$_POST['json_excel'] = json_decode($_POST['json_excel'],true);

	$class = new specials_AdminSupport();
	$fp = fopen('/tmp/excel.tmp', 'w+');

	foreach ($_POST['json_excel'] as $fields) {
		fputcsv($fp, $fields);
	}

	fclose($fp);
	echo file_get_contents('/tmp/excel.tmp');
	exit;

}
/*
 * END FILE
 */

