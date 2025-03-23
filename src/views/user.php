<?php

declare(strict_types=1);

/**
 * @var array $data
 */
extract(array_merge(
   ['logo' => '<span style="font-size: 100px"><i class="bx bx-sad"></i></span>'],
   $data
));
?>
<html lang="en">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">

   <title><?= "$APP_NAME-$status_code" ?>| Server Error</title>

   <!-- Fonts -->
   <link rel="preconnect" href="https://fonts.gstatic.com">
   <link href="https://fonts.googleapis.com/css2?family=Nunito&amp;display=swap" rel="stylesheet">
   <link rel="manifest" href="<?= "$ROOT_PATH/manifest.json" ?>" />
   <link rel="shortcut icon" href="<?= "$ROOT_PATH/favicon.svg" ?>" type="image/x-icon">
   <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
   <style>
   /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */
   html {
      line-height: 1.15;
      -webkit-text-size-adjust: 100%;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.5
   }

   body {
      margin: 0
   }

   *,
   :after,
   :before {
      box-sizing: border-box;
      border: 0 solid #e2e8f0;

   }

   a {
      color: inherit;
      text-decoration: inherit;
      background-color: transparent;
   }

   .bg-white {
      --bg-opacity: 1;
      background-color: #fff;
      background-color: rgba(255, 255, 255, var(--bg-opacity))
   }

   .bg-gray-100 {
      --bg-opacity: 1;
      background-color: #f7fafc;
      background-color: rgba(247, 250, 252, var(--bg-opacity))
   }

   .border-gray-200 {
      --border-opacity: 1;
      border-color: #edf2f7;
      border-color: rgba(237, 242, 247, var(--border-opacity))
   }

   .border-gray-400 {
      --border-opacity: 1;
      border-color: #cbd5e0;
      border-color: rgba(203, 213, 224, var(--border-opacity))
   }

   .border-t {
      border-top-width: 1px
   }

   .border-r {
      border-right-width: 1px
   }

   .flex {
      display: flex
   }

   .flex-column {
      flex-direction: column;
   }

   .grid {
      display: grid
   }

   .hidden {
      display: none
   }

   .items-center {
      align-items: center
   }

   .justify-center {
      justify-content: center
   }

   .font-semibold {
      font-weight: 600
   }

   .h-5 {
      height: 1.25rem
   }

   .h-8 {
      height: 2rem
   }

   .h-16 {
      height: 4rem
   }

   .text-sm {
      font-size: .875rem
   }

   .text-lg {
      font-size: 1.125rem
   }

   .leading-7 {
      line-height: 1.75rem
   }

   .mx-auto {
      margin-left: auto;
      margin-right: auto
   }

   .mx-4 {
      margin-left: 1rem;
      margin-right: 1rem
   }

   .ml-1 {
      margin-left: .25rem
   }

   .mt-2 {
      margin-top: .5rem
   }

   .mr-2 {
      margin-right: .5rem
   }

   .ml-2 {
      margin-left: .5rem
   }

   .mt-4 {
      margin-top: 1rem
   }

   .ml-4 {
      margin-left: 1rem
   }

   .mt-8 {
      margin-top: 2rem
   }

   .ml-12 {
      margin-left: 3rem
   }

   .-mt-px {
      margin-top: -1px
   }

   .max-w-xl {
      max-width: 46rem
   }

   .max-w-6xl {
      max-width: 72rem
   }

   .min-h-screen {
      min-height: 100vh
   }

   .overflow-hidden {
      overflow: hidden
   }

   .p-6 {
      padding: 1.5rem
   }

   .py-4 {
      padding-top: 1rem;
      padding-bottom: 1rem
   }

   .px-4 {
      padding-left: 1rem;
      padding-right: 1rem
   }

   .px-6 {
      padding-left: 1.5rem;
      padding-right: 1.5rem
   }

   .pt-8 {
      padding-top: 2rem
   }

   .fixed {
      position: fixed
   }

   .relative {
      position: relative
   }

   .top-0 {
      top: 0
   }

   .right-0 {
      right: 0
   }

   .shadow {
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px 0 rgba(0, 0, 0, .06)
   }

   .text-center {
      text-align: center
   }

   .text-gray-200 {
      --text-opacity: 1;
      color: #edf2f7;
      color: rgba(237, 242, 247, var(--text-opacity))
   }

   .text-gray-300 {
      --text-opacity: 1;
      color: #e2e8f0;
      color: rgba(226, 232, 240, var(--text-opacity))
   }

   .text-gray-400 {
      --text-opacity: 1;
      color: #cbd5e0;
      color: rgba(203, 213, 224, var(--text-opacity))
   }

   .text-gray-500 {
      --text-opacity: 1;
      color: #a0aec0;
      color: rgba(160, 174, 192, var(--text-opacity))
   }

   .text-gray-600 {
      --text-opacity: 1;
      color: #718096;
      color: rgba(113, 128, 150, var(--text-opacity))
   }

   .text-gray-700 {
      --text-opacity: 1;
      color: #4a5568;
      color: rgba(74, 85, 104, var(--text-opacity))
   }

   .text-gray-900 {
      --text-opacity: 1;
      color: #1a202c;
      color: rgba(26, 32, 44, var(--text-opacity))
   }

   .uppercase {
      text-transform: uppercase
   }

   .underline {
      text-decoration: underline
   }

   .antialiased {
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale
   }

   .tracking-wider {
      letter-spacing: .05em
   }

   .w-5 {
      width: 1.25rem
   }

   .w-8 {
      width: 2rem
   }

   .w-auto {
      width: auto
   }

   .grid-cols-1 {
      grid-template-columns: repeat(1, minmax(0, 1fr))
   }

   @-webkit-keyframes spin {
      0% {
         transform: rotate(0deg)
      }

      to {
         transform: rotate(1turn)
      }
   }

   @keyframes spin {
      0% {
         transform: rotate(0deg)
      }

      to {
         transform: rotate(1turn)
      }
   }

   @-webkit-keyframes ping {
      0% {
         transform: scale(1);
         opacity: 1
      }

      75%,
      to {
         transform: scale(2);
         opacity: 0
      }
   }

   @keyframes ping {
      0% {
         transform: scale(1);
         opacity: 1
      }

      75%,
      to {
         transform: scale(2);
         opacity: 0
      }
   }

   @-webkit-keyframes pulse {

      0%,
      to {
         opacity: 1
      }

      50% {
         opacity: .5
      }
   }

   @keyframes pulse {

      0%,
      to {
         opacity: 1
      }

      50% {
         opacity: .5
      }
   }

   @-webkit-keyframes bounce {

      0%,
      to {
         transform: translateY(-25%);
         -webkit-animation-timing-function: cubic-bezier(.8, 0, 1, 1);
         animation-timing-function: cubic-bezier(.8, 0, 1, 1)
      }

      50% {
         transform: translateY(0);
         -webkit-animation-timing-function: cubic-bezier(0, 0, .2, 1);
         animation-timing-function: cubic-bezier(0, 0, .2, 1)
      }
   }

   @keyframes bounce {

      0%,
      to {
         transform: translateY(-25%);
         -webkit-animation-timing-function: cubic-bezier(.8, 0, 1, 1);
         animation-timing-function: cubic-bezier(.8, 0, 1, 1)
      }

      50% {
         transform: translateY(0);
         -webkit-animation-timing-function: cubic-bezier(0, 0, .2, 1);
         animation-timing-function: cubic-bezier(0, 0, .2, 1)
      }
   }

   @media (min-width:640px) {
      .sm\:rounded-lg {
         border-radius: .5rem
      }

      .sm\:block {
         display: block
      }

      .sm\:items-center {
         align-items: center
      }

      .sm\:justify-start {
         justify-content: flex-start
      }

      .sm\:justify-between {
         justify-content: space-between
      }

      .sm\:h-20 {
         height: 5rem
      }

      .sm\:ml-0 {
         margin-left: 0
      }

      .sm\:px-6 {
         padding-left: 1.5rem;
         padding-right: 1.5rem
      }

      .sm\:pt-0 {
         padding-top: 0
      }

      .sm\:text-left {
         text-align: left
      }

      .sm\:text-right {
         text-align: right
      }
   }

   @media (min-width:768px) {
      .md\:border-t-0 {
         border-top-width: 0
      }

      .md\:border-l {
         border-left-width: 1px
      }

      .md\:grid-cols-2 {
         grid-template-columns: repeat(2, minmax(0, 1fr))
      }
   }

   @media (min-width:1024px) {
      .lg\:px-8 {
         padding-left: 2rem;
         padding-right: 2rem
      }
   }

   @media (prefers-color-scheme:dark) {
      .dark\:bg-gray-800 {
         --bg-opacity: 1;
         background-color: #2d3748;
         background-color: rgba(45, 55, 72, var(--bg-opacity))
      }

      .dark\:bg-gray-900 {
         --bg-opacity: 1;
         background-color: #1a202c;
         background-color: rgba(26, 32, 44, var(--bg-opacity))
      }

      .dark\:border-gray-700 {
         --border-opacity: 1;
         border-color: #4a5568;
         border-color: rgba(74, 85, 104, var(--border-opacity))
      }

      .dark\:text-white {
         --text-opacity: 1;
         color: #fff;
         color: rgba(255, 255, 255, var(--text-opacity))
      }

      .dark\:text-gray-400 {
         --text-opacity: 1;
         color: #cbd5e0;
         color: rgba(203, 213, 224, var(--text-opacity))
      }
   }

   body {
      font-family: 'Nunito', sans-serif;
   }

   .error-page {
      z-index: 9999 !important;
      position: fixed !important;
      top: 0 !important;
      width: 100vw !important;
      min-height: 100vh !important;
   }

   .back-btn {
      color: #a0aec0;
      margin-top: 30px;
      transition: all .5s;
   }

   .back-btn:hover {
      opacity: 0.5;
   }

   .table {
      width: 100%;
      border: 1px solid #1a202c;
      padding: 5px;
      color: #fff;
   }

   .table th,
   td {
      border: 1px solid #2d3748;
      padding: 15px;
   }
   </style>
</head>

<body class="antialiased">
   <div
        class="relative flex flex-column items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
      <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
         <?php if (isset($logo)) : ?>
         <div class="flex items-center justify-center text-gray-500 py-4"><?= $logo ?></div>
         <?php endif ?>
         <div class="flex items-center justify-center px-4 text-lg text-gray-500 tracking-wider" style="font-size:30px">
            <?= $status_code ?> | SERVER ERROR</div>
         <table class="table">
            <tr>
               <th>Error</th>
               <td><?= $message ?></td>
            </tr>
         </table>
      </div>
      <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
         <div class="flex w-100">
            <a class="back-btn flex justify-center items-center" href=" <?= $ROOT_PATH ?>"><i
                  class='bx bx-home-alt'></i> Home</a>
            <a class="back-btn flex justify-center items-center mx-4" onclick="window.history.back()"
               style="cursor: pointer; font-size:larger;">[&nbsp;<i class='bx bx-left-arrow-circle'></i> Back
               ]</a>
            <a class="back-btn flex justify-center items-center" onclick="window.location.reload()"
               style="cursor: pointer;"><i class='bx bx-refresh'></i> Reload</a>
         </div>
      </div>
   </div>
</body>

</html>