(function (erfindergeistCalendar, $, undefined) {
  const days = ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"];

  function getGermanDateString(date) {
    const event = new Date(date);
    const options = {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    };

    return event.toLocaleDateString("de-DE", options);
  }

  // expose
  erfindergeistCalendar.getGermanDateString = getGermanDateString;

  function getGermanDateDayString(date) {
    const event = new Date(date);
    const options = {
      day: "2-digit",
    };

    return event.toLocaleDateString("de-DE", options);
  }

  erfindergeistCalendar.getGermanDateDayString = getGermanDateDayString;

  function getGermanTimeString(date) {
    const event = new Date(date);

    return event.toLocaleTimeString("de-De", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  erfindergeistCalendar.getGermanTimeString = getGermanTimeString;

  function getGermanWeekDayShortString(date) {
    const event = new Date(date);
    const options = {
      weekday: "short",
    };

    return event.toLocaleDateString("de-DE", options);
  }

  erfindergeistCalendar.getGermanWeekDayShortString = getGermanWeekDayShortString;

  function getData() {
    $.getJSON(`https://${location.hostname}/wp-json/erfindergeist/v2/events`)
      .done(function (json) {
         render(json);

        
      })
      .fail(function (jqxhr, textStatus, error) {
        const err = textStatus + ", " + error;
        console.log("Request Failed: " + err);
        renderError();
      });
  }

  function renderError(renderType) {
    const html = `
      <div class="wp-block-coblocks-column__inner has-no-padding has-no-margin">
         Error Loading data.
      </div>
   `;
    $(`#${renderType}`).html(html);
  }

  function render(data) {
    let calenderTemplate = "";

    const fallbackCalenderTemplate = `
      <div class="container p-0 text-dark">
      {{#each items}}
        <div class="row">
          <div class="col-1" style="font-size: 3rem">{{weekDayShort}}</div>
          <div class="col">{{summary}}, {{description}}, {{location}}{{startDate}}, {{startTime}}, 
           {{endDate}}, {{endTime}}, {{weekDayShort}}

            <div>
            {{#each tags as | tag |}}
              <span class="badge text-bg-primary">{{tag}}</span>
            {{/each}}
            </div>
          </div>
        </div>
      {{/each}}
      </div>
    `;

    try {
      calenderTemplate = document.getElementById("egj_calendar_template").innerHTML;
    } catch (e) {
      calenderTemplate = fallbackCalenderTemplate;
    }

    const template = Handlebars.compile(calenderTemplate);

    // Daten sind bereits transformiert vom Backend
    $("#gcalendarList").html(template(data));

    jQuery("#gcalendarPrintButton").click(function (event) {
      event.preventDefault();
      console.log("gcalender print");
      // jQuery(".visible-on-print").offset({ left: 0, top: 0 })
      window.print();
    });
  }

  erfindergeistCalendar.init = function () {
    if (document.getElementById("gcalendarList")) {
      getData();
    }

  };
})((window.erfindergeistCalendar = window.erfindergeistCalendar || {}), jQuery);

jQuery(document).ready(function () {
  Handlebars.registerHelper("include", function (arr, key) {
    if (arr && Array.isArray(arr)) {
      return arr.includes(key);
    }
    return false;
  });

  Handlebars.registerHelper("filter", function (arr, key) {
    if (arr && Array.isArray(arr)) {
      return arr.filter((dataItem) => dataItem?.tags.includes(key));
    }

    return arr;
  });

  Handlebars.registerHelper("isOdd", function (num) {
    return num % 2;
  });

  Handlebars.registerHelper("isEven", function (num) {
    return !(num % 2);
  });

  Handlebars.registerHelper("first", function (arr, num) {
    return arr.slice(0, num);
  });

  Handlebars.registerHelper("today", function () {
    return erfindergeistCalendar.getGermanDateString(new Date())
  });

  Handlebars.registerHelper("getDateFromDt", function (str) {
    const dateTime = new Date(str);
    return erfindergeistCalendar.getGermanDateString(dateTime);
  });

  Handlebars.registerHelper("getTimeFromDt", function (str) {
    const dateTime = new Date(str);
    return erfindergeistCalendar.getGermanTimeString(dateTime);
  });   

  erfindergeistCalendar.init();
});

