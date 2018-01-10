<?php
/*
  * User have to be existing in global_user table
 *  Khoa. B
  */

// Format: username, first_name, last_name, email, user_type, roles(array), "site=blank"
$file_input = __DIR__."/internal_user.csv"; // alow csv file only, if no file exists, then it will read from $users_list below;
$users_list = array(
    array(
        "username"  =>  "example_username",
        "first_name"    =>  "accounting",
        "last_name" =>  "team",
        "email" =>  "noemail@inhouseusa.com",
        "user_type" => 1,
        "roles" =>  "IHSAccounting", // array(1,2,3,4) or defined string like IHSAccounting, admin, accounting..
        "parties"   =>  array(1),
        "site"  =>  "all", // single string as site name, all, or an array("site1","site2","site3"),
        "reset_roles"   => false, // set to true will reset the roles, false will add new roles
        "reset_contact"   => false, // set to true will reset first_name, last_name, email in contact,
        "deactivate"    => false  // set to true will deactivate user
    ),
    /*
    array(
        "username"  =>  "cynthia.dymon@inhouseusa.com",
        "first_name"    =>  "Cynthia",
        "last_name" =>  "Dymon",
        "email" =>  "cynthia.dymon@inhouseusa.com",
        "user_type" => 1,
        "roles" =>  array(1,2,11, 23),
        "parties"   =>  array(1),
        "site"  =>  array("site1","site2","site3")
    ),
    array(
        "username"  =>  "cynthia.dymon@inhouseusa.com",
        "first_name"    =>  "Cynthia",
        "last_name" =>  "Dymon",
        "email" =>  "cynthia.dymon@inhouseusa.com",
        "user_type" => 1,
        "roles" =>  "IHSAccounting",
        "parties"   =>  array(1),
        "site"  =>  "all"
    ),
    */
);

ini_set('include_path','.:../includes/:../includes/libs/:/usr/share/pear/');
require_once('daos/GenericDAO.php');
require_once('classes/User.php');
require_once('classes/Roles.php');
require_once('misc.php');

class InHouseUser {
    private $connections = array();
    private $output = array();
    /*
     * Init all Connections First Load
     */
    public function __construct()
    {
        $DirectoryHandle = opendir('/var/www/conf/tandem/');
        $this->connections = array();
        $k=0;
        while($FileName = readdir($DirectoryHandle)){
            if(preg_match('/\.ini$/i',$FileName)){
                if(strlen($FileName) < 10) {
                    continue;
                } else {
                    $OPTIONS = parse_ini_file("/var/www/conf/tandem/$FileName", true);
                    $ConnectionObj->HOST = $OPTIONS['PG_SQL']['HOST'];
                    $ConnectionObj->USER = $OPTIONS['PG_SQL']['USER'];
                    $ConnectionObj->PASSWORD = $OPTIONS['PG_SQL']['PASSWORD'];
                    $ConnectionObj->DBNAME = $OPTIONS['PG_SQL']['DBNAME'];
                    $ConnectionObj->OPTIONS = $OPTIONS;
                    $this->connections[$k]['connection'] = $ConnectionObj;
                    $this->connections[$k]['options'] = $OPTIONS;
                    unset($ConnectionObj);
                    $OPTIONS = array();
                    $k++;
                }

            }
        }
    }


	/**
	 * @param $ConnexionsDAO
	 * @param $User
	 * @return mixed
	 */
	public function getGlobalUserID($ConnexionsDAO, $User) {
	    $global_user_id = $ConnexionsDAO->Execute('select * from commondata.global_users where user_name=?',  $User['username'])->fetchNextObj()->global_user_id;
	    if(empty($global_user_id)) {
		    $global_user_id = $this->createGlobalUser($ConnexionsDAO, $User);
	    }
	    if(empty($global_user_id)) {
	    	die("Can not create user ".$User['username']);
	    }
	    return $global_user_id;
    }


	/**
	 * @param $ConnexionsDAO
	 * @param $User
	 * @return mixed
	 */
	public function createGlobalUser($ConnexionsDAO, $User) {
	    $ConnexionsDAO->Execute("INSERT INTO commondata.global_users (first_name,last_name, password, user_name) values(?, ? , ? , ?)", array($User['first_name'],$User['last_name'], md5($User['email']), $User['username']));
	    return $ConnexionsDAO->Execute('select * from commondata.global_users where user_name=?',  $User['username'])->fetchNextObj()->global_user_id;

    }

    /*
     * input user list
     */
    public function createUsers($Users = array(), $file_input) {
    	$tmp = explode(".",$file_input);
	    $ext = strtolower(end($tmp));
	    if($ext == "csv" && file_exists($file_input) && filesize($file_input) > 0) {
	    	$Users = array();
	    	$csv = new CSVFile($file_input);
		    foreach($csv as $user) {
			    $tmp_user = array();
			    if(!is_array($user)) {
			        continue;
                }
			    foreach($user as $key=>$value) {
				    $key = strtolower(str_replace(' ','_',trim($key)));
				    if(trim($key) == "") {
				        continue;
                    }
				    if(in_array($key,array("sites","add_to_sites"))) {
				    	$key = "site";
				    }
				    if($key == "email_address") {
				    	$key = "email";
				    }
				    if($key == "user_name") {
				    	$key = "username";
				    }
				    if($key == "party") {
				    	$key = "parties";
				    }
				    if($value === "t" || strtolower($value) === "true") {
				        $value = true;
                    }
                    if($value === "f" || strtolower($value) === "false") {
                        $value = false;
                    }
				    $tmp_user[$key] = $value;
			    }
			    if(!isset($tmp_user['username'])) {
			    	$tmp_user['username'] = strtolower($tmp_user['email']);
			    }
			    $Users[] = $tmp_user;
		    }
	    }
        foreach($Users as $user) {
        	if(!empty($user['username'])) {
                $user['username'] = strtolower($user['username']);
		        $this->createAnUser($user);
	        }
        }
    }

    /*
     * input an user
     */
    public function createAnUser($User = array()) {
        $pw = md5(strtolower($User['username']));
	    $site_by_comma = is_string($User['site']) ? explode(",",$User['site']) : array();
        foreach($this->connections as $con){
                if($User['site']!="all" && $User['site']!=""
                    && ((!is_array($User['site']) && strtolower($User['site'])!=strtolower($con['connection']->USER))
		                || (is_array($User['site']) && (!in_array(strtolower($con['connection']->USER), $User['site'])))
		                || (count($site_by_comma) >= 2 && !in_array(strtolower($con['connection']->USER), $site_by_comma))
	                    )

                ) {
                    // skip
                    // echo "SKIP 1";
                    continue;
                }
                try {
                    if($con['connection']->USER== "" || is_null($con['connection']->USER )) {
                        echo "SKIP ..";
                        continue;
                    }
                    $ConnexionsDAO = new GenericDAO($con['connection']);
                    $rs = $ConnexionsDAO->Execute('select * from users where user_name=?', $User['username'])->fetchNextObj();
                    echo "User ID = ".$rs->user_id;
                    $UserClass = new User($con['connection']);
                    $UserRolesType = $User['roles'];

                    $Parties = is_string($User['parties']) ? explode("||",trim($User['parties'])) : $User['parties'];
                    $UserClass->UserType = $User['user_type'];
                    $Roles = is_string($UserRolesType) ? explode(",",trim($UserRolesType)) : $UserRolesType;
                    $UserClass->Parties = array();

                    // replace party name to $UserClass->Parties
                    foreach($Parties as $key=>$party) {
                        $party = trim($party);
                        if($party!="") {
                            if(!is_numeric($party)) {
                                $r = $ConnexionsDAO->Execute("SELECT * FROM parties WHERE party_name=?", array($party))->fetchNextObj();
                                if(!is_null($r->party_id)) {
                                    $UserClass->Parties[] = $r->party_id;
                                }
                            } else {
                                $UserClass->Parties[] = $party;
                            }
                        }
                    }
                    if(empty($UserClass->Parties)) {
                        $UserClass->Parties[] = 1;
                    }

                    // replace party name to $UserClass->Roles
                    foreach($Roles as $key=>$role) {
                        $role = trim($role);
                        if($role!="") {
                            if(!is_numeric($role)) {
                                $r = $ConnexionsDAO->Execute("SELECT * FROM commondata.role_types WHERE role_name=?", array($role))->fetchNextObj();
                                if(!is_null($r->role_type_id)) {
                                    $UserClass->Roles[] = $r->role_type_id;
                                }
                            } else {
                                $UserClass->Roles[] = $role;
                            }
                        }
                    }

                    if (is_null($rs->user_id)) {
                        echo "Creating ...";
                        $UserClass->monthly_max = '0';
                        $UserClass->UserName = strtolower($User['username']);
                        $UserClass->FirstName = $User['first_name'];
                        $UserClass->LastName =  $User['last_name'];
                        $UserClass->Email = strtolower($User['email']);
                        $UserClass->password_hash = $pw;
                        $UserClass->LoginEnabledFlag = true;
	                    $UserClass->CompanyName = $User['company_name'];
	                    $UserClass->OfficePhone = $User['office_phone'];
	                    $UserClass->CellPhone = $User['cell_phone'];
                        $UserClass->GlobalUserID = $this->getGlobalUserID($ConnexionsDAO,$User);

                        $UserClass->Create(false, false);
                        echo "Created User {$User['username']} => {$con['connection']->USER} ";
                        $rs = $ConnexionsDAO->Execute('select * from users where user_name=?', $User['username'])->fetchNextObj();
                        $user_id = $rs->user_id;
                        $contact_id = $rs->contact_id;

                     } else {
                        // do update
                        echo " Updating ...";
                        $user_id = $rs->user_id;
                        $contact_id = $rs->contact_id;
                        $global_user_id = $this->getGlobalUserID($ConnexionsDAO,$User);
                        $schema = $con['connection']->USER;
                        if(!empty($global_user_id) && !empty($contact_id) && !empty($schema)) {
							// check & fix linking
	                        $sql = "SELECT COUNT(*) as total FROM commondata.local_global_users WHERE global_user_id=? AND contact_id=? AND database_name=? ";
	                        $r = $ConnexionsDAO->Execute($sql, array($global_user_id, $contact_id, $schema))->fetchNextObj();
	                        if($r->total < 1) {
	                        	$sql = "INSERT INTO commondata.local_global_users (database_name, contact_id , global_user_id) VALUES('{$schema}',{$contact_id}, {$global_user_id})";
		                        $ConnexionsDAO->Execute($sql);
		                        echo " Created Local Global Link ...";
	                        }
                        }
                        if((Int)$user_id >0 ) {
                            $sql = "UPDATE users set user_type=? WHERE user_id=?";
                            $ConnexionsDAO->Execute($sql, array($UserClass->UserType, $user_id));
                            if((Int)$contact_id > 0) {
                                $sql = "UPDATE contacts SET contact_type=? WHERE contact_id=?";
                                $ConnexionsDAO->Execute($sql, array($UserClass->UserType, $contact_id));
                            }
                            // re insert user roles
                            if(isset($User['reset_roles']) && $User['reset_roles'] == false ) {
                                // keep old roles, clean & prepare for not duplicated
                                $role_list = "";
                                foreach($UserClass->Roles as $role) {
                                    $role_list .= "{$role} ,";
                                }
                                $role_list = trim(rtrim($role_list,","));
                                if(!empty($role_list)) {
                                    $sql = "DELETE FROM users_roles WHERE user_id=? and role_id IN({$role_list})";
                                    $ConnexionsDAO->Execute($sql,array($user_id));
                                }
                            } else {
                                // reset all roles
                                $sql = "DELETE FROM users_roles WHERE user_id=? ";
                                echo " Reset Roles ";
                                $ConnexionsDAO->Execute($sql,array($user_id));
                            }
                            // reset first_name, last_name, email
                            if(isset($User['reset_contact']) && $User['reset_contact'] == true && (Int)$contact_id > 0) {
                                $sql = "UPDATE contacts SET first_name=? ,last_name=? , contact_email=? WHERE contact_id=?";
                                echo " Reset Contact ";
                                $ConnexionsDAO->Execute($sql, array($User['first_name'], $User['last_name'], $User['email'], $contact_id));
                            }
                            // deactivate user
                            if(isset($User['deactivate']) && $User['deactivate'] === true && (Int)$contact_id > 0) {
                                $sql = "UPDATE contacts SET enabled_flag=false ,deactivate_flag=true WHERE contact_id=?";
                                $ConnexionsDAO->Execute($sql, array($contact_id));
                                $sql = "UPDATE users SET login_enabled_flag=false , deactivate_flag=true WHERE contact_id=?";
                                $ConnexionsDAO->Execute($sql, array($contact_id));
                            } elseif((Int)$contact_id > 0) {
                                $sql = "UPDATE contacts SET enabled_flag=true ,deactivate_flag=false WHERE contact_id=?";
                                $ConnexionsDAO->Execute($sql, array($contact_id));
                                $sql = "UPDATE users SET login_enabled_flag=true , deactivate_flag=false WHERE contact_id=?";
                                $ConnexionsDAO->Execute($sql, array($contact_id));
                            }

                            // setup new roles
                            foreach($UserClass->Roles as $role) {
                                $sql = "INSERT INTO users_roles (user_id, role_id) values(?,?)";
                                $ConnexionsDAO->Execute($sql,array($user_id,$role));
                            }
                            // re insert party
                            $sql = "DELETE FROM party_contacts WHERE contact_id=? ";
                            $ConnexionsDAO->Execute($sql,array($contact_id));
                            foreach($UserClass->Parties as $party) {
                                $sql = "INSERT INTO party_contacts (party_id, contact_id) values(?,?)";
                                $ConnexionsDAO->Execute($sql,array($party,$contact_id));
                            }
                            echo "Updated User {$User['username']} => {$con['connection']->USER} ";
                        }
                    } // end updating

                    if($user_id > 0 && $contact_id > 0) {
                        // monthly_max , company_name, office_phone , cell_phone
                        // fha_approved_flag
                        // assignment_threshold
                        // preferred_flag
                        // address1
                        // city
                        // state
                        // zipcode


                        // license_level, license_exp, license_state

                        // insurance_carrier, insurance_policy, insurance_exp
                    }

                    echo "<br>\r\n";

                } catch (Exception $e) {
                    poop($e,'Creating User Error','CreateUsers.log');
                    echo "ERROR ON User {$User['username']} => {$con['connection']->USER} <br>\r\n";
                }

        }

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


$Inhouse = new InHouseUser();
$Inhouse->createUsers($users_list,$file_input);










