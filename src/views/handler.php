<?php

// declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= "$APP_NAME-$status_code" ?>| Server Error</title>
   <link rel="manifest" href="<?= $ROOT_PATH ?>/manifest.json">
   <link rel="shortcut icon" href="<?= "$ROOT_PATH/favicon.svg" ?>" type="image/x-icon">
   <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css"
      integrity="sha384-r4NyP46KrjDleawBgD5tp8Y7UzmLA05oM1iAEQ17CSuDqnUK2+k9luXQOfXJCJ4I" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/firacode/6.2.0/fira_code.min.css"
      integrity="sha512-MbysAYimH1hH2xYzkkMHB6MqxBqfP0megxsCLknbYqHVwXTCg9IqHbk+ZP/vnhO8UEW6PaXAkKe2vQ+SWACxxA=="
      crossorigin="anonymous" referrerpolicy="no-referrer" />
   <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<style>
   *:not(i) {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      /* Change this to your desired font */
   }

   body {
      background-color: #f8f9fa;
      --danger-color: #f21e50;
      --danger-border: rgba(181, 23, 60, 0.44);
      --danger-bg: #f21e4f2b;
      --success-color: #00a000;
      --success-bg: #00a0001b;
      --success-border: rgba(0, 139, 0, 0.44);
      --warning-color: #ff771d;
      --warning-bg: #ff771d1c;
      --warning-border: rgba(194, 91, 22, 0.44);
      --info-color: #7280fd;
      --info-bg: #7280fd21;
      --info-border: rgba(89, 100, 202, 0.44);
      --card-bg: #f8f8f8;
      --text-color: #111;
      --text-gray: #5e5e5e;
   }

   body.dark {
      background-color: #181818;
      --card-bg: #212121;
      --text-color: #f8f9fa;
      --text-gray: #a3a7aa;
      --danger-color: rgb(144, 33, 59);
   }

   .code {
      font-family: 'Fira Code', monospace;
   }

   .card {
      background: var(--card-bg);
   }

   .container {
      margin-top: 100px;
   }

   .text-color {
      color: var(--text-color);
   }

   .text-gray {
      color: var(--text-gray);
   }

   .fw-bold {
      font-weight: bold;
   }

   .fw-bolder {
      font-weight: bolder;
   }

   .fw-500 {
      font-weight: 500;
   }

   .circle {
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50% !important;
   }

   .circle-small {
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      width: 30px;
      height: 30px;
   }

   .circle-medium {
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      width: 40px;
      height: 40px;
   }

   .circle-large {
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      width: 50px;
      height: 50px;
   }

   .raised {
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.275);
   }

   .rounded-2 {
      border-radius: 0.5rem;
   }

   .rounded-3 {
      border-radius: 1rem;
   }

   .rounded-4 {
      border-radius: 1.5rem;
   }

   /** Alerts */

   .alert-danger {
      color: var(--danger-color) !important;
      background-color: var(--danger-bg);
      border-color: var(--danger-border);
   }

   .alert-success {
      color: var(--success-color);
      background-color: var(--success-bg);
      border-color: var(--success-border);
   }

   .alert-warning {
      color: var(--warning-color);
      background-color: var(--warning-bg);
      border-color: var(--warning-border);
   }

   .alert-info {
      color: var(--info-color);
      background-color: var(--info-bg);
      border-color: var(--info-border);
   }

   .table {
      width: 100%;
      border: none !important;
      padding: 5px;
      border-collapse: separate;
   }

   .table th,
   .table td {
      border: 1px solid var(--card-bg);
      padding: 10px;
   }


   .alert-danger .table th,
   .alert-danger .table td {
      border: 1px solid var(--danger-border);
      color: var(--danger-color);
   }

   .alert-warning .table th,
   .alert-warning .table td {
      border: 1px solid var(--warning-border);
      color: var(--warning-color);
   }

   .alert-info .table th,
   .alert-info .table td {
      border: 1px solid var(--info-border);
      color: var(--info-color);
   }


   .alert-success .table th,
   .alert-success .table td {
      border: 1px solid var(--success-border);
      color: var(--success-color);
   }

   .back-btn {
      color: var(--text-color);
      padding: 10px 20px;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s;
   }

   .back-btn:hover {
      color: var(--success-color);
      opacity: 0.8;
   }

   /* Nav Tab Buttons */
   .nav-tabs {
      border: none !important;
   }

   .nav-link {
      border-radius: 0 !important;
      color: var(--text-color);
      background-color: var(--card-bg);
      border: none !important;
   }

   .nav-link:hover {
      filter: brightness(0.8);
      border: none !important;
      border-radius: 0 !important;
   }

   .nav-item.danger .nav-link.active,
   .nav-item.danger .nav-link:active {
      color: var(--danger-color) !important;
      background-color: var(--danger-bg) !important;
      border: none !important;
   }

   .nav-item.warning .nav-link.active,
   .nav-item.warning .nav-link:active {
      color: var(--warning-color) !important;
      background-color: var(--warning-bg) !important;
      border: none !important;
   }

   .nav-item.info .nav-link.active,
   .nav-item.info .nav-link:active {
      color: var(--info-color) !important;
      background-color: var(--info-bg) !important;
      border: none !important;
   }

   .nav-item.success .nav-link.active,
   .nav-item.success .nav-link:active {
      color: var(--success-color) !important;
      background-color: var(--success-bg) !important;
      border: none !important;
   }
</style>
<script defer>
   document.addEventListener('DOMContentLoaded', () => {
      $theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.body.classList.add($theme);
      const btn = document.querySelector('.toggle-theme');

      btn.addEventListener('click', () => {
         const $icon = document.body.classList.contains('dark') ?
            "<i class='bx bx-sun text-color'></i>" :
            "<i class='bx bx-moon text-color'></i>";
         btn.innerHTML = $icon;
         document.body.classList.toggle('dark');
      });
   });
</script>

<body>
   <div class="container mt-2">
      <div class="row">
         <div class="col-md-12 my-3">
            <div class="d-flex justify-content-between align-items-center">
               <h3
                  class="text-dark dark:text-white text-center p-1 d-flex justify-content-center align-items-center">
                  <div class="alert <?= "alert-$color" ?> border-0 circle-medium my-0 mr-2 p-3">
                     <i class="bx bx-error-circle" style="font-size: medium;"></i>
                  </div>
                  <div class="text-color">Internal Server Error| <span class="text-gray"
                        style="font-size: medium;"><?= $object ?>::class</span>
                  </div>
               </h3>
               <div class="d-flex">
                  <button class="btn toggle-theme circle-small">
                     <i class='bx bx-moon text-color'></i>
                  </button>
               </div>
            </div>
         </div>
      </div>
      <div class="card border-0 py-0 mb-5 shadow-lg">
         <div class="row">
            <div class="col-md-12">
               <div class="row">
                  <div class="col-md-12">
                     <div class="px-5 pt-5 pb-2">
                        <span
                           class="fw-500 alert <?= "alert-$color" ?> rounded-4 border-0 px-3 py-2"><?= $class ?></span>
                     </div>
                  </div>
                  <div class="col-md-9">
                     <div class="px-5 py-2">
                        <h5 class="text-gray"><?= $message ?></h5>
                     </div>
                  </div>
                  <div class="col-md-3">
                     <div class="px-5 pt-2">
                        <p class="btn btn-sm btn-secondary btn-block raised" style="font-size: smaller;">PHP
                           <?= PHP_VERSION ?> ( <?= PHP_OS ?> )</p>
                     </div>
                     <div class="px-5 pb-2 w-100 text-center text-gray"
                        style="margin-top: -10px; font-size: smaller;">
                        Anode Error Handler| <?= EDD_VERSION ?></div>
                  </div>
               </div>
            </div>
            <div class="col-12">
               <!-- Tab Navs -->
               <ul class="nav nav-tabs" id="errorTabs" role="tablist">
                  <li class="nav-item <?= $color ?>" role="presentation">
                     <a class="nav-link active" id="error-tab" data-bs-toggle="tab" data-bs-target="#error"
                        type="button" role="tab" aria-controls="error" aria-selected="true">Error
                        Details</a>
                  </li>
                  <li class="nav-item info" role="presentation">
                     <a class="nav-link" id="trace-tab" data-bs-toggle="tab" data-bs-target="#trace"
                        type="button" role="tab" aria-controls="trace" aria-selected="false">Debug Trace</a>
                  </li>
                  <li class="nav-item success" role="presentation">
                     <a class="nav-link" id="help-tab" data-bs-toggle="tab" data-bs-target="#help"
                        type="button" role="tab" aria-controls="help" aria-selected="false">How to
                        Fix</a>
                  </li>
               </ul>
               <!-- End of Tab Navs -->
               <!-- Tab Content -->
               <div class="tab-content" id="errorTabsContent">
                  <div class="tab-pane fade show active" id="error" role="tabpanel" aria-labelledby="error-tab">
                     <div class="alert <?= "alert-$color" ?> rounded-0 border-0 m-0 p-5" role="alert">
                        <h6 class="alert-heading"><?= "@ $class$type$function" ?><span class="code">()</span></h6>

                        <div class="table-responsive">
                           <table class="table">
                              <?php foreach ($args as $e => $v): ?>
                                 <tr>
                                    <th><?= ucfirst($e) ?></th>
                                    <td><?= $v ?></td>
                                 </tr>
                              <?php endforeach ?>
                           </table>
                        </div>
                     </div>
                  </div>
                  <div class="tab-pane fade" id="trace" role="tabpanel" aria-labelledby="trace-tab">
                     <div class="alert alert-info rounded-0 border-0 m-0 p-5" role="alert">
                        <?= $backtrace ?>
                     </div>
                  </div>
                  <div class="tab-pane fade" id="help" role="tabpanel" aria-labelledby="help-tab">
                     <div class="alert alert-success rounded-0 border-0 m-0 p-5" role="alert">
                        <p>The server encountered an internal error or misconfiguration and was unable to complete
                           your
                           request.</p>
                        <hr>
                        <p>This should not happen. Ideally all <i>notices</i>, <i>warnings</i>, and
                           <i>errors</i> should be handled in your code. To avoid this:
                        </p>
                        <ol>
                           <li>Always define variables before you use them.</li>
                           <li>Remember to check that a file exists before including it.</li>
                           <li>Always handle potential errors according to coding standards. I.e. Show a relevant
                              error
                              to
                              the
                              user, fail silently, or log events to a file on the server.</li>
                        </ol>
                     </div>
                  </div>
               </div>
               <!-- End of Tab Content -->
            </div>
            <div class="col-12 d-flex justify-content-center align-items-center my-3">
               <a class="back-btn" href=" <?= $ROOT_PATH ?>">
                  <i class='bx bx-home-alt'></i> Home</a>
               <a class="back-btn mx-4" onclick="window.history.back()">
                  [&nbsp;<i class='bx bx-left-arrow-circle'></i> Back ]
               </a>
               <a class="back-btn" onclick="window.location.reload()"><i class='bx bx-refresh'></i> Reload</a>
            </div>
         </div>
      </div>
   </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
   integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script>

</html>