document.addEventListener('DOMContentLoaded', function () {
    window.nb = window.nb || {};
    nb.config = {};
    nb.nb_DateSelection = {};
    nb.nb_DateSelection.nb_CheckInField_DatePicker;
    nb.nb_DateSelection.nb_CheckOutField_DatePicker;
    nb.config.nb_baseUrl = "https://book.nightsbridge.com/";

    nb.config.nb_bbid = "12845"; // Change this to your BBID
    nb.config.initialised = false;
    nb.config.customFormat = "d-M-Y";
    nb.config.language = "en-GB";

    nb.nb_DateWidget = function (nb) {
        let durationOfStay = 1;
        let checkInDate = new Date();
        let checkOutDate = new Date();
        let today = new Date();
        let yesterday = new Date();
        let tomorrow = new Date();
        yesterday.setDate(checkInDate.getDate() - 1);
        tomorrow.setDate(today.getDate() + 1);
        checkOutDate.setDate(checkInDate.getDate() + 1);

        function nb_CheckAvailabilityOnBookingForm() {
            let nb_bookingUrl = nb.config.nb_baseUrl + nb.config.nb_bbid + "?startdate=" + encodeURI(getBookingFormFormat(checkInDate)) + "&enddate=" + encodeURI(getBookingFormFormat(checkOutDate));
            document.getElementById("availabilityIframe").src = nb_bookingUrl;
            document.getElementById("availabilityModal").style.display = "block";
            return nb_bookingUrl;
        }

        function updateElementText(element, text, parent = false) {
            parent ? document.getElementById(element).parentElement.innerText = text : document.getElementById(element).innerText = text;
        }

        function updateElementValue(element, text) {
            document.getElementById(element).value = text;
        }

        function getElementValue(element) {
            return document.getElementById(element).value;
        }

        function getCustomDateFormat(date) {
            let dateArray = date.toLocaleString(nb.config.language).slice(0, 10).split("/"),
                dateString = date.toString(nb.config.language).slice(0, 10).split(" ");
            return dateString[2] + "-" + dateString[1] + "-" + dateArray[2];
        }

        function getBookingFormFormat(date) {
            const options = { year: 'numeric', day: '2-digit', month: '2-digit' };
            const dateFormatted = date.toLocaleString(nb.config.language, options).replace(/\//g, '-');
            const dateString = dateFormatted.split("-");
            return dateString[2] + "-" + dateString[1] + "-" + dateString[0];
        }

        function dateDiff(date1, date2, sign = false) {
            let diff_ms = date1 <= date2 ? date2 - date1 : date1 - date2;
            let min = 60,
                hour = 24,
                sec = 60,
                milli = 1000;
            return Math.floor(((diff_ms / milli) / sec / min / hour));
        }

        function daysInMs(numberOfDays) {
            let min = 60,
                hour = 24,
                sec = 60,
                milli = 1000;
            return numberOfDays * hour * min * sec * milli;
        }

        document.addEventListener("readystatechange", () => {
            if (!nb.config.initialised) {
                nb.config.initialised = true;
                initWidget(nb.config.initialised);
            }
        });

        let initWidget = function (initialised) {
            nb.nb_DateSelection.nb_CheckInField_DatePicker = document.getElementById("nb_CheckInDate").flatpickr({
                "dateFormat": nb.config.customFormat,
                "minDate": yesterday,
                "mode": "single",
                "disableMobile": "true",
                "altInput": "true",
                "altFormat": nb.config.customFormat,
                "onChange": function (selectedDates, dateStr) {
                    checkInDate = selectedDates[0];
                    if (checkInDate >= checkOutDate) {
                        const newDate = new Date(checkInDate.getTime() + daysInMs(durationOfStay));
                        checkOutDate = newDate;
                        nb.nb_DateSelection.nb_CheckOutField_DatePicker.setDate(getCustomDateFormat(checkOutDate), true, nb.config.customFormat);
                        document.querySelector("#nb_CheckOutDate+input").value = getCustomDateFormat(checkOutDate);
                    } else {
                        durationOfStay = dateDiff(checkInDate, checkOutDate);
                    }
                }
            });

            nb.nb_DateSelection.nb_CheckOutField_DatePicker = document.getElementById("nb_CheckOutDate").flatpickr({
                "dateFormat": nb.config.customFormat,
                "minDate": tomorrow,
                "mode": "single",
                "disableMobile": "true",
                "altInput": "true",
                "altFormat": nb.config.customFormat,
                "onChange": function (selectedDates, dateStr) {
                    checkOutDate = selectedDates[0];
                    durationOfStay = dateDiff(checkInDate, checkOutDate);
                }
            });

            document.getElementById("nb_checkAvailabilityBtn").addEventListener("click", function () {
                nb_CheckAvailabilityOnBookingForm();
            });

            document.querySelector(".close-btn").addEventListener("click", function () {
                document.getElementById("availabilityModal").style.display = "none";
            });

            window.onclick = function (event) {
                if (event.target === document.getElementById("availabilityModal")) {
                    document.getElementById("availabilityModal").style.display = "none";
                }
            };
        };

        return {
            initWidget: initWidget
        };
    }(window.nb);
});