<?php

include "db.php";

$q = trim($_GET['q']);

if($q=="") exit();

$stmt=$conn->prepare("

SELECT DO_ID AS id,'Delivery Order' AS type

FROM delivery_order

WHERE DO_ID LIKE CONCAT('%', ?, '%')

UNION

SELECT Invoice_ID,'Invoice'

FROM invoice

WHERE Invoice_ID LIKE CONCAT('%', ?, '%')

LIMIT 10

");

$stmt->bind_param("ss",$q,$q);

$stmt->execute();

$result=$stmt->get_result();

while($row=$result->fetch_assoc()){

    echo "

<div class='live-item'

onclick=\"fillSearch('".$row['id']."')\">

<strong>".$row['id']."</strong>

<br>

<small>".$row['type']."</small>

</div>

";

}

?>