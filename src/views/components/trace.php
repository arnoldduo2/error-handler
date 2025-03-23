<?php

declare(strict_types=1);

$list = explode("\n", $backtrace);

$newList = [];
foreach ($list as $key => $value) {
   $newList[$key] = str_replace("#$key", '', $value);
}

?>
<div class="row">
   <div class="col-12">
      <h6 class="text-gray">Debug Trace</h6>
      <div class="table-responsive">
         <table class="table text-gray">
            <tbody>
               <?php foreach ($newList as $key => $value) : ?>
               <tr>
                  <th>Stack Trace: <?= $key ?></th>
                  <td><?= $value ?></td>
               </tr>
               <?php endforeach; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>