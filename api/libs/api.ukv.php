<?php

/*
 * UKV cable TV accounting implementation
 */

class UkvSystem {

    protected $tariffs = array();
    protected $users=array();

    public function __construct() {
        $this->loadTariffs();
        $this->loadUsers();
    }

    /*
     * loads all tariffs into private tariffs prop
     * 
     * @return void
     */

    protected function loadTariffs() {
        $query = "SELECT * from `ukv_tariffs`";
        $alltariffs = simple_queryall($query);
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $each) {
                $this->tariffs[$each['id']] = $each;
            }
        }
    }

    /*
     * creates new tariff into database
     * 
     * @param $name  tariff name
     * @param $price tariff price 
     * 
     * @return void
     */

    public function tariffCreate($name, $price) {
        $name = mysql_real_escape_string($name);
        $price = mysql_real_escape_string($price);
        $query = "INSERT INTO `ukv_tariffs` (`id`, `tariffname`, `price`) VALUES (NULL, '" . $name . "', '" . $price . "');";
        nr_query($query);
        log_register("UKV TARIFF CREATE `" . $name . "` WITH PRICE `" . $price . "`");
    }

    /*
     * deletes some existing tariff from database
     * 
     * @param $tariffid existing tariff ID
     * 
     * @return void
     */

    public function tariffDelete($tariffid) {
        $tariffid = vf($tariffid, 3);
        $tariffName = $this->tariffs[$tariffid]['tariffname'];
        $query = "DELETE from `ukv_tariffs` WHERE `id`='" . $id . "'";
        nr_query($query);
        log_register("UKV TARIFF DELETE `" . $tariffName . "`  [" . $tariffid . "]");
    }

    /*
     * saves some tariff params into database
     * 
     * @param $tariffid    existing tariff ID
     * @param $tariffname  new name of the tariff
     * @param $price       new tariff price
     */

    public function tariffSave($tariffid,$tariffname,$price) {
        $tariffid = vf($tariffid, 3);
        $tariffname=  mysql_real_escape_string($tariffname);
        $price= mysql_real_escape_string($price);
        $query="UPDATE `ukv_tariffs` SET `tariffname` = '".$tariffname."', `price` = '".$price."' WHERE `id` = '".$tariffid."';";
        nr_query($query);
        log_register("UKV TARIFF CHANGE `" . $tariffname . "` WITH PRICE `".$price."`  [" . $tariffid . "]");
    }
    
    /*
     * renders CaTV tariffs list with some controls
     */
    public function renderTariffs() {
        
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Tariff name'));
        $cells.= wf_TableCell(__('Tariff Fee'));
        $rows=  wf_TableRow($cells, 'row1');
        
        if (!empty($this->tariffs)) {
            foreach ($this->tariffs as $io=>$each) {
                $cells=  wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['tariffname']);
                $cells.= wf_TableCell($each['price']);
                $rows.=  wf_TableRow($cells, 'row3');
            }
        }
        
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }
    
    
    /*
     * loads all users from database to private prop users
     * 
     * @return void
     */
    protected function loadUsers() {
        $query="SELECT * from `ukv_users`";
        $allusers= simple_queryall($query);
        if (!empty($allusers)) {
            foreach ($allusers as $io=>$each) {
                $this->users[$each['id']]=$each;
            }
        }
    }

    /*
     * registers new users into database and returns new user ID
     * 
     * @return int 
     */
    public function userCreate() {
        $curdate=  date("Y-m-d H:i:s");
        $cash=0;
        $active=1;
        $query="
            INSERT INTO `ukv_users` (
                            `id` ,
                            `contract` ,
                            `tariffid` ,
                            `cash` ,
                            `active` ,
                            `realname` ,
                            `passnum` ,
                            `passwho` ,
                            `passdate` ,
                            `ssn` ,
                            `phone` ,
                            `mobile` ,
                            `regdate` ,
                            `city` ,
                            `street` ,
                            `build` ,
                            `apt` ,
                            `inetlogin` ,
                            `notes`
                            )
                            VALUES (
                            NULL ,
                            NULL ,
                            NULL ,
                            '".$cash."',
                            '".$active."',
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            '".$curdate."',
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL ,
                            NULL
                            );  ";
        $newUserId=  simple_get_lastid('ukv_users');
        $result=$newUserId;
        log_register("UKV REGISTER USER ((".$newUserId."))");
        return ($result);
    }
    
    
}

?>