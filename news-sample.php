 <?

/* true для тестирования, в боевом режиме - $debug=false */ 
$debug=false;





/* данные для соединения с сервером WebAsyst */

$hostname = 'localhost';/* адрес сервера - имя домена или IP-адрес, локально - localhost*/
$username = 'root'; /* имя пользователя для подключения к базе */ 
$password = '368811';/* пароль пользователя*/
$db_name = 'webasyst309';/* имя базы данных */
$newstable = 'SC_news_table';/* имя таблицы новостей, по умолчанию SC_news_table */


/* данные для соединения с сервером Joomla 1 */
$joomla[]=array(
'enabled' => true,/* для отключения установить параметр в false */
'server_id' => '1',/* уникальный идентификатор сервера */
'hostname' => 'localhost',/* адрес сервера - имя домена или IP-адрес, локально - localhost */
'username' => 'jomla2wa', /* имя пользователя для подключения к базе */
'password' => 'joomla2wa',/* пароль пользователя*/
'db_name' => 'joomla2wa',/* имя базы данных */
'db_prefix' => 'j25',/* префикс таблиц Joomla, указан в config.php Joomla*/
'cat_id'=>'64'/* id категории, из которой будут импортироваться новости - Joomla >Материалы > Менеджер категорий > ID (в таблице справа) */
);

/* данные для соединения с сервером Joomla 2*/
$joomla[]=array(
'enabled' => true,
'server_id' => '2',
'hostname' => 'localhost',
'username' => 'joomla15',
'password' => '84n67h4',
'db_name' => 'joomla15',
'db_prefix' => 'j25',
'cat_id'=>'66'
);




/*********************************************************************************/
// header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set("UTC");

$date_format="Y-m-d H:i:s";

$date = new DateTime();
$now=  $date->format($date_format);

$update= file_get_contents('timestamp.txt');
$date = new DateTime($update);
$update=  $date->format($date_format);


$link_wa=mysql_connect($hostname,$username,$password) or die(mysql_error()); 
mysql_select_db($db_name,$link_wa) or die(mysql_error()); 
mysql_set_charset('utf8',$link_wa);

        
        
$row = mysql_fetch_object(mysql_query("select max(`priority`) as `_priority` from {$db_name}.{$newstable}"));
$priority=$row->_priority+1;
//echo $priority;
        
        

foreach ( $joomla as $server) {
$i=$server['server_id'];
        if($server['enabled'])
                {
                $link_joomla=mysql_connect($server['hostname'],$server['username'],$server['password'])or die(mysql_error());
                mysql_select_db($server['db_name'],$link_joomla) or die(mysql_error()); 
                mysql_set_charset('utf8', $link_joomla);
                // mysql_query( "SET SESSION time_zone = 'UTC'" ); 
                
                
                
                $table=$server['db_name'].".".$server['db_prefix']."_content";
              
                $sql = "SELECT * FROM $table WHERE `created` BETWEEN '{$update}' AND '{$now}' OR `modified` BETWEEN '{$update}' AND '{$now} 'AND `state` = '1' AND `catid` = '{$server['cat_id']}' ORDER BY `ordering` DESC;";

                     

                        $res = mysql_query($sql,$link_joomla) or die(mysql_error()); 
                        $count = mysql_num_rows($res);
                        echo " server{$i} {$server['hostname']}: {$count}<BR>";
                        if ($count > 0) {                              
                               
                while ($row=mysql_fetch_array($res)) {
               
                        $id=$row['id'];
                        $title=mysql_real_escape_string($row['title']);
                        $text=mysql_real_escape_string($row['fulltext']);
                        if($text==''){
                                $text=mysql_real_escape_string($row['introtext']);
                                }

                      

                        $news_id=$row['id'];
                        $date = date_create($row['created']);
                        $add_date= date_format($date, 'Y-m-d');
                        $add_stamp = strtotime($add_date);
                        $sql = "SELECT * FROM {$db_name}.{$newstable} WHERE `news_id` = {$news_id} AND `server_id`={$i};";
                        $res2 =mysql_query($sql,$link_wa) or die(mysql_error()); 

                        if( mysql_num_rows($res2)>0) {
                         echo "server {$i} update news {$id} <br>";
                                $sql = "UPDATE {$db_name}.{$newstable} SET`add_date` = '{$add_date}', `title_ru` = '{$title}', `textToPublication_ru` = '{$text}', `add_stamp` = {$add_stamp}, `priority`={$priority} WHERE `news_id` = {$news_id} AND `server_id` = {$i};";
                        }
                        else {
                            echo "server {$i } add new news {$id} <br>";
                        $sql = "INSERT INTO {$db_name}.{$newstable} ( `add_date`,`title_ru`,`textToPublication_ru`,`add_stamp`,`news_id`,`server_id`,`priority`) VALUES ('{$add_date}','{$title}', '{$text}', {$add_stamp},  {$id},{$i},{$priority});";
                        }

                           
mysql_query($sql,$link_wa) or die(mysql_error());
           
             
 
                      
                                echo " <center><b> news id $id</b> </center>";
                                echo "<b>created:</b> {$row['created']}<br>";
                                echo "<b>modified:</b> {$row['modified']}<br>";
                                echo "<b>title:</b> {$title}<br>";
                                echo "<b>text:</b> {$text}<hr>";
                      
                             
                           }//while
        mysql_free_result($res);
        mysql_free_result($res2);
         
       
        }
      
        }
      
}//foreach
   
mysql_close($link_wa);
mysql_close($link_joomla);
file_put_contents('timestamp.txt', $now);
?>
