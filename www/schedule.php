<?php
/*
 *      schedule.php
 *      
 *      Copyright 2010 Daniel Böhmer <daniel.boehmer@it2007.ba-leipzig.de> and
 *                     Patrick Nicolaus <patrick.nicolaus@it2007.ba-leipzig.de>
 *      
 *      This file is part of Hotelres.
 * 
 *      Hotelres is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      Hotelres is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */



$PAGE_TITLE='Belegungsplan';
$PAGE_HEADLINE='Belegungsplan';

include('include/header.inc');

if ($_POST['update'] == 1)
{
		good_query('UPDATE guests SET firstname="'.$_POST['firstname'].'", lastname="'.$_POST['lastname'].'", street="'.$_POST['street'].'", number="'.$_POST['number'].'", zip="'.$_POST['zip'].'", city="'.$_POST['city'].'", country="'.$_POST['country'].'", phone="'.$_POST['phone'].'", email="'.$_POST['email'].'" WHERE id="'.$_POST['guest'].'"');
		
		good_query('UPDATE bookings SET comment="'.$_POST['comment'].'" WHERE guest="'.$_POST['guest'].'"');
		
		messages_add("<p>".t("Daten erfolgreich aktualisiert.")."</p>");
}// if

if ($_GET['delete'] > 0)
{
	$del_bookings = good_query_table('SELECT id as bookingid, guest as guestid FROM bookings WHERE id="'.$_GET['delete'].'"');
	
	good_query('DELETE FROM bookings WHERE id="'.$del_bookings[0]['bookingid'].'"');
	good_query('DELETE FROM guests WHERE id="'.$del_bookings[0]['guestid'].'"');
	
	messages_add("<p>".t("Buchung erfolgreich storniert.")."</p>");
}

messages_show();

if ($_GET['schedule']==1)
    {
        // user requested calendar
        $show_schedule=1;
        
        $year=$_GET['year'];
        $month=$_GET['month'];
    }
elseif ((!$_GET['show']) && (!$_GET['edit']) && (!$_POST['update']) && (!$_POST['delete']))
    {
        // no action selected -> show today's calendar
        $show_schedule=1;
        
        $year=date("Y");
        $month=date("n");
    }
elseif ($_GET['schedule']==0)
	{
		$show_schedule=0;
		
		$year=$_GET['year'];
        $month=$_GET['month'];
	}

?>

<form action="schedule.php" method="get">
	<table>
        <tr><td><?php echo t("Monat");?>:</td>
          <td><select name="month" size="1"> 
     <?php
     
            for ($i=1; $i<=12; $i++)
                {
                    echo '<option value="'.$i.'"';
                    
                    if ($month==$i)
                        echo ' selected="selected"';
                    
                    echo '>'.$MONTH_NAMES[$i-1].'</option>';
                }
         /*   <option value="1"><?php echo t("Januar");?></option>
            <option value="2"><?php echo t("Februar");?></option>
            <option value="3"><?php echo t("März");?></option>
            <option value="4"><?php echo t("April");?></option>
            <option value="5"><?php echo t("Mai");?></option>
            <option value="6"><?php echo t("Juni");?></option>
            <option value="7"><?php echo t("Juli");?></option>
            <option value="8"><?php echo t("August");?></option>
            <option value="9"><?php echo t("September");?></option>
            <option value="10"><?php echo t("Oktober");?></option>
            <option value="11"><?php echo t("November");?></option>
            <option value="12"><?php echo t("Dezember");?></option>       */ ?>
        </select></td>
        
        <td><?php echo t("Jahr");?>:</td>
          <td><select name="year" size="1">
          	<?php
			for($i=$year-5; $i <= $year+5; $i++)
			{
            	echo "<option value=\"".$i."\"";
                
                if ($year==$i)
                    echo ' selected="selected"';
                
                echo ">".$i."</option>";
           	}
			?>
        </select></td></tr>
	</table>
    
    <input type="hidden" name="update" value="0">
    <input type="hidden" name="schedule" value="1">
           
	<input type="submit" value="<?php echo t("Anzeigen");?>">
</form>
<br />

<?php

if ($show_schedule == 1)
{
$number_day = strftime("%w",mktime(0,0,0,$month,1,$year));
$count_days = date("t",mktime(0,0,0,$month,1,$year));

/*  <= just add/remove a 2nd dash "/" on the left side to toggle
// only for debugging:
echo utf8_encode(strftime("%B %Y",mktime(0,0,0,$month,1,$year)));
//*/

echo '<table><tr>';

foreach ($WEEKDAY_NAMES as $day)
    echo '<th>'.$day.'</th>';

echo '</tr><tr>';

for ($i=1; $i<$number_day; $i++)
    {
        echo '<td>&nbsp;</td>';
    }

$j=$i;
		
$count_all_rooms = db_count_all_rooms();	


// colors for visualization:
//                     R    G    B
$color_empty = array( 255, 127, 127);
$color_okay = array(  127, 255, 127);
$color_full = array(  255, 191, 191);
// aimed utilization rate
$percentage_aimed = 80; // in %

			
for ($i=1; $i<=$count_days; $i++)
{
	$count_engaged_rooms = db_count_engaged_rooms(own_date_format("%Y-%m-%d",$i.".".$month.".".$year,0),own_date_format("%Y-%m-%d",$i.".".$month.".".$year,0));
	
	if ($count_all_rooms == 0)
    {
        $util=0;
        $utilization = "-.--";
    }
	else
	{
        $util = (($count_engaged_rooms * 100) / $count_all_rooms);
		$utilization = number_format($util,2);
	}
	

    // CALCULATE COLORS FOR VISUALIZATION OF UTIL.
    
    // trivial colors
    if ($util == 0)
        $color=$color_empty;
    elseif ($util == $percentage_aimed)
        $color=$color_okay;
    elseif($util == 100)
        $color=$color_full;
    
    // values between given colors -> calculate mix colors
    else
        {
            if ($util < $percentage_aimed)
                {
                    $color2=$color_empty;
                    $opacity=$util/$percentage_aimed-1;
                }
            else // util > percentage_aimed
                {
                    $color2=$color_full;
                    $opacity=-($util-$percentage_aimed)/(100-$percentage_aimed);
                }
            
            // interpolate for all three RGB values
            for ($n=0; $n<3; $n++)
                $color[$n]=$color_okay[$n]+round($opacity*($color_okay[$n]-$color2[$n]));
        }
    
    //generate HTML code
    $colorcode = array2htmlColor($color);
    
	echo '<td style="background-color:'.$colorcode.';">';
    echo '<a href="'.url_add_parameter($_SERVER['ORIG_PATH_INFO']."?month=".$month."&year=".$year."&update=0&schedule=0","show",own_date_format("%Y-%m-%d",$i.".".$month.".".$year,0)).'">'.$i.'</a><br /><pre> '.'('.$utilization.'%)'.'</pre> </td>';
			
	if ($j % 7 == 0)
	{
		echo '</tr><tr>';
	}// if
	
	$j++;
} //for

echo '</tr></table>';
}// if

if ((! empty($_GET['show'])) OR (! empty($_POST['date'])))
{
	if (! empty($_GET['show']))
	{
		$bookingdate = $_GET['show'];
	}
	else
	{
		$bookingdate = $_POST['date'];
	}	
	
	echo t("Zimmerbelegung am ").own_date_format("%d.%m.%Y",$bookingdate,0)."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=".url_add_parameter($_SERVER['ORIG_PATH_INFO']."?month=".own_date_format("%m",$bookingdate,(60*60*24*-1))."&year=".own_date_format("%Y",$bookingdate,(60*60*24*-1))."&update=0&schedule=0","show",own_date_format("%Y-%m-%d",$bookingdate,(60*60*24*-1))).">".t("vorheriger")."</a>"."&nbsp;&nbsp;&nbsp;<a href=".url_add_parameter($_SERVER['ORIG_PATH_INFO']."?month=".own_date_format("%m",$bookingdate,(60*60*24))."&year=".own_date_format("%Y",$bookingdate,(60*60*24))."&update=0&schedule=0","show",own_date_format("%Y-%m-%d",$bookingdate,(60*60*24))).">".t("nächster")."</a>";

	$bookings = good_query_table('SELECT a.id as bookingid, a.room, a.guest as guestid, a.begin, a.end, a.comment, a.persons, b.id, b.name as name, c.id, c.firstname, c.lastname, b.name FROM bookings as a right join rooms as b on a.room = b.id left join guests as c on a.guest = c.id WHERE begin<="'.$bookingdate.'" AND end>="'.$bookingdate.'" ORDER BY c.lastname ASC');
	
	echo '<form><table>';
	echo '<tr><th>'.t("").'</th>
			  <th>'.t("Zimmer").'</th>
			  <th>'.t("Vorname").'</th>
			  <th>'.t("Nachname").'</th>
			  <th>'.t("Personen").'</th>
			  <th>'.t("Beginn").'</th>
			  <th>'.t("Ende").'</th>
			  <th>'.t("Kommentar").'</th>
			  <th>'.t("").'</th></tr>';
			
	foreach ($bookings as $booking)
	{
		echo '<tr><td><a href="'.url_add_parameter($_SERVER['ORIG_PATH_INFO'].'?show='.$bookingdate.'',"edit",$booking['guestid']).'">'.t("Bearbeiten").'</a></td>';
			
		echo '<td>'.$booking['name'].'</td>';
		echo '<td>'.$booking['firstname'].'</td>';
		echo '<td>'.$booking['lastname'].'</td>';
		echo '<td>'.$booking['persons'].'</td>';
		echo '<td>'.own_date_format("%d.%m.%Y",$booking['begin'],0).'</td>';
		echo '<td>'.own_date_format("%d.%m.%Y",$booking['end'],0).'</td>';
		echo '<td>'.$booking['comment'].'</td>';
		
		echo '<td><a href="'.url_add_parameter($_SERVER['ORIG_PATH_INFO'].'?show='.$bookingdate.'',"delete",$booking['bookingid']).'">'.t("Stornieren").'</a></td></tr>';
	}// foreach
			
	echo '</table>';	
	echo '</form>';
}// if


if ($_GET['edit'] > 0)
{

$guests_guest = good_query_table('SELECT a.id,a.firstname, a.lastname, a.street, a.number, a.zip, a.city, a.country, a.phone, a.email, b.id, b.comment, b.guest FROM guests AS a RIGHT JOIN bookings AS b ON a.id = b.guest WHERE a.id="'.$_GET['edit'].'"');

echo '<br />Kundendaten von '.$guests_guest[0]['firstname'].' '.$guests_guest[0]['lastname'].'';
?>

<br />
<form action="schedule.php" method="post">

<table>
	<td><table border="0">
	<tr><td><?php echo t("Vorname");?>:</td>
	<td><input type="text" name="firstname" value="<?php echo $guests_guest[0]['firstname']?>"></td></tr>

    <tr><td><?php echo t("Nachname");?>:</td>
    <td><input type="text" name="lastname" value="<?php echo $guests_guest[0]['lastname']?>"></td></tr>
    
    <tr><td><?php echo t("Strasse");?>:</td>
    <td><input type="text" name="street" value="<?php echo $guests_guest[0]['street']?>"></td></tr>

    <tr><td><?php echo t("Hausnummer");?>:</td>
    <td><input type="text" name="number"value="<?php echo $guests_guest[0]['number']?>"></td></tr>
    
    <tr><td><?php echo t("PLZ");?>:</td>
    <td><input type="text" name="zip" value="<?php echo $guests_guest[0]['zip']?>"></td></tr>
    
    <tr><td><?php echo t("Wohnort");?>:</td>
    <td><input type="text" name="city" value="<?php echo $guests_guest[0]['city']?>"></td></tr>
    
    <tr><td><?php echo t("Land");?>:</td>
    <td><input type="text" name="country" value="<?php echo $guests_guest[0]['country']?>"></td></tr>
    
    <tr><td><?php echo t("Telefon");?>:</td>
    <td><input type="text" name="phone" value="<?php echo $guests_guest[0]['phone']?>"></td></tr>
    
    <tr><td><?php echo t("E-Mail");?>:</td>
    <td><input type="text" name="email" value="<?php echo $guests_guest[0]['email']?>"></td></tr>
	</table></td>

	<td><table border="0">
    	<tr><td><?php echo t("Kommentar");?>:</td>
        <td><textarea name="comment" rows="5" cols="42"><?php echo $guests_guest[0]['comment']?></textarea></td></tr>
        
        </table></td></tr>
    </table></td>
</table>

<input type="hidden" name="update" value="1">
<input type="hidden" name="schedule" value="0">
<input type="hidden" name="date" value="<?php echo $_GET['show'];?>"  method="get">
<input type="hidden" name="guest" value="<?php echo $guests_guest[0]['id'];?>">
    
<input type="submit" value="<?php echo t("Aktualisieren");?>">

</form>

<?php

}// if edit

include('include/footer.inc');

?>
