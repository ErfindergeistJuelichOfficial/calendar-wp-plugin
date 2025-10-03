<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Erfindergeist Jülich e.V. Termine</title>
    <link rel="stylesheet" href="./bootstrap.min.css">
    <link rel="stylesheet" href="./gcalender.css">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
  </head>
  <body>
    <main>
      
      <?PHP
        switch ($_GET['page']) {
          case "0":
            echo "i equals 0";
            break;
          case "staender":
            include_once('pagePrintStaender.html');
            break;
          case 'all':
          default:
            include_once('pagePrintAll.html');
        }
      ?>
    
      <div
        style="margin-left: auto; margin-right: auto"
        id="gcalendarList"
      >
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line end"></div>
        <hr />
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line end"></div>
        <hr />
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line end"></div>
        <hr />
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line end"></div>
        <hr />
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line"></div>
        <div class="shimmerBG content-line end"></div>
      </div>

      <div class="container m-2 no-print" style="z-index: 100">
        <button id="gcalendarPrintButton"type="button" class="btn btn-primary">Drucken</button>
        <button onclick="location.href='./termine.php';" type="button" class="btn btn-primary">Alle Termine</button>
        <button onclick="location.href='./termine.php?page=staender';" type="button" class="btn btn-primary">Ständer Termine (nächste drei)</button>
      </div>
    </main>
    
    <script src="https://erfindergeist.org/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
    <script src="https://erfindergeist.org/wp-includes/js/jquery/jquery-migrate.min.js?ver=3.4.1" id="jquery-migrate-js"></script>
    <script src="./handlebars.js?ver=4.7.8" id="handlebars-js"></script>
    <script src="./gcalendar.js?ver=1.6"></script>
  </body>
</html>