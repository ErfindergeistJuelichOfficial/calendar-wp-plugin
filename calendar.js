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

  function getGermanTimeString(date) {
    const event = new Date(date);

    return event.toLocaleTimeString("de-De", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function getGermanWeekDayShortString(date) {
    const event = new Date(date);
    const options = {
      weekday: "short",
    };

    return event.toLocaleDateString("de-DE", options);
  }

  function getData(renderType) {
    $.getJSON(`https://${location.hostname}/wp-json/erfindergeist/v1/feature`)
      .done(function (feature) {
        if( feature && feature.feature === "google") {
          $.getJSON(`https://${location.hostname}/wp-json/erfindergeist/v1/gcalendar`)
            .done(function (json) {
              switch (renderType) {
                case "gcalendarList":
                  renderNormal(json);
                  break;
                case "gcalendarListShort":
                  renderShort(json);
                  break;
              }
            })
            .fail(function (jqxhr, textStatus, error) {
              const err = textStatus + ", " + error;
              console.log("Request Failed: " + err);
              renderError(renderType);
            });
        }

        if( feature && feature.feature === "nextcloud") {
          $.getJSON(`https://${location.hostname}/wp-json/erfindergeist/v1/events`)
            .done(function (json) {
              switch (renderType) {
                case "gcalendarList":
                  renderNormal(json, feature.feature);
                  break;
                 }
            })
            .fail(function (jqxhr, textStatus, error) {
              const err = textStatus + ", " + error;
              console.log("Request Failed: " + err);
              renderError(renderType);
            });
        }
      }
    )

  }

  function renderError(renderType) {
    const html = `
      <div class="wp-block-coblocks-column__inner has-no-padding has-no-margin">
         Error Loading data.
      </div>
   `;
    $(`#${renderType}`).html(html);
  }

  function transformICal(data) {
    const newItems = data.VEVENT.map((item) => {
      const patern = /#[a-zA-Z0-9äüö]*/g;
      const tags = item.DESCRIPTION.match(patern) ?? [];

      let filteredDescription = item.DESCRIPTION;
      tags.forEach((tag) => {
        filteredDescription = filteredDescription.replace(tag, "");
      });

      return {
      summary: item.SUMMARY ?? "",
      description: filteredDescription ?? "",
      location: item.LOCATION ?? "",
      startDateDay: item.DTSTART_array
        ? getGermanDateDayString(item.DTSTART_array[2])
        : "",
      endDateDay: item.DTEND_array
        ? getGermanDateDayString(item.DTEND_array[2])
        : "",
      startTime: item.DTSTART_array
        ? getGermanTimeString(item.DTSTART_array[2])
        : "",
      endTime: item.DTEND_array
        ? getGermanTimeString(item.DTEND_array[2])
        : "",
      weekDayShort: item.DTSTART_array
        ? getGermanWeekDayShortString(item.DTSTART_array[2])
        : "",
      tags:
        tags && Array.isArray(tags) && tags.length > 0
          ? tags.map((tag) => tag.substring(1))
          : [],
      };
    });

    const newData = {
      items: newItems,
    };

    return newData;
  }

  function transform(data) {
    const newItems = data.items.map((item) => {
      const patern = /#[a-zA-Z0-9äüö]*/g;
      const tags = item.description.match(patern) ?? [];

      let filteredDescription = item.description;
      tags.forEach((tag) => {
        filteredDescription = filteredDescription.replace(tag, "");
      });

      let startDate = item.start?.dateTime
        ? getGermanDateString(item.start.dateTime)
        : "";
      let endDate = item.end?.dateTime
        ? getGermanDateString(item.end.dateTime)
        : "";

      return {
        summary: item.summary ?? "",
        description: filteredDescription ?? "",
        location: item.location ?? "",
        startDate,
        startDateDay: item.start?.dateTime
          ? getGermanDateDayString(item.start.dateTime)
          : "",
        endDateDay: item.end?.dateTime
          ? getGermanDateDayString(item.end.dateTime)
          : "",
        startTime: item.start?.dateTime
          ? getGermanTimeString(item.start.dateTime)
          : "",
        endDate,
        endTime: item.end?.dateTime
          ? getGermanTimeString(item.end.dateTime)
          : "",
        weekDayShort: item.start.dateTime
          ? getGermanWeekDayShortString(item.start.dateTime)
          : "",
        sameDay: startDate === endDate,
        tags:
          tags && Array.isArray(tags) && tags.length > 0
            ? tags.map((tag) => tag.substring(1))
            : [],
      };
    });

    const newData = {
      items: newItems,
    };

    return newData;
  }

  function renderShort(data) {
    if (data?.items && Array.isArray(data.items) && data.items?.length > 0) {
      let html = `<div class="wp-block-coblocks-column__inner has-no-padding has-no-margin">`;
      for (let i = 0; i < data.items.length; i++) {
        const ele = data.items[i];

        let startDate = "";
        let endDate = "";
        let startTime = "";
        let endTime = "";

        if (ele?.start?.dateTime && ele?.end.dateTime) {
          startDate = new Date(ele.start.dateTime).toLocaleDateString();
          endDate = new Date(ele.end.dateTime).toLocaleDateString();
          startTime = new Date(ele.start.dateTime)
            .toLocaleTimeString()
            .slice(0, -3);
          endTime = new Date(ele.end.dateTime)
            .toLocaleTimeString()
            .slice(0, -3);
        }

        let dateFormated = "";

        if (startDate === endDate) {
          dateFormated = `[${
            days[new Date(ele.start.dateTime).getDay()]
          }, ${startDate}, ${startTime} - ${endTime}]`;
        } else {
          dateFormated = `[${startDate}, ${startTime} - ${endDate}, ${endTime}]`;
        }

        html += `<p>`;
        html += `${dateFormated} <br>`;
        html += ele.summary ? `${ele.summary} <br>` : "";
        html += ele.description ? `${ele.description}` : "";
        html += `</p>`;
        html += i + 1 !== data.items.length ? `<hr>` : "";
      }

      html += `</div>`;
      $("#gcalendarListShort").html(html);
    }
  }

  function renderNormal(data, feature) {
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
      calenderTemplate = document.getElementById("gcalendarTemplate").innerHTML;
    } catch (e) {
      calenderTemplate = fallbackCalenderTemplate;
    }

    const template = Handlebars.compile(calenderTemplate);

    if(feature && feature === "nextcloud") {
      $("#gcalendarList").html(template(transformICal(data)));
    } else {
      $("#gcalendarList").html(template(transform(data)));
    }
    

    jQuery("#gcalendarPrintButton").click(function (event) {
      event.preventDefault();
      console.log("gcalender print");
      // jQuery(".visible-on-print").offset({ left: 0, top: 0 })
      window.print();
    });
  }

  erfindergeistCalendar.init = function () {
    if (document.getElementById("gcalendarList")) {
      getData("gcalendarList");
    }

    if (document.getElementById("gcalendarListShort")) {
      getData("gcalendarListShort");
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
    return gCalendar.getGermanDateString(new Date())
  });

  erfindergeistCalendar.init();
});

