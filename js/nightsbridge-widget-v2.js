document.addEventListener('DOMContentLoaded', function () {
    window.nb = window.nb || {};
    nb.config = {};
    nb.nb_DateSelection = {};
    nb.nb_DateSelection.nb_CheckInField_DatePicker;
    nb.nb_DateSelection.nb_CheckOutField_DatePicker;
    nb.config.nb_baseUrl = "https://book.nightsbridge.com/";

    // Use localized data
    nb.config.nb_bbid = nbConfig.bbid;
    nb.config.initialised = false;
    nb.config.customFormat = nbConfig.customFormat;
    nb.config.language = nbConfig.language;

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

        /**
         * @function nb_CheckAvailabilityOnBookingForm
         * @description Generate NightsBridge booking URL and display it in the "availabilityIframe" modal.
         * @returns {string|null} The generated NightsBridge booking URL or null if an error occurs.
         */
        function nb_CheckAvailabilityOnBookingForm() {
            try {
                if (!nb || !nb.config || !checkInDate || !checkOutDate) {
                    console.error("Configuration or date variables are not properly initialized.");
                    return null;
                }

                let nb_bookingUrl = `${nb.config.nb_baseUrl}${nb.config.nb_bbid}?startdate=${encodeURIComponent(getBookingFormFormat(checkInDate))}&enddate=${encodeURIComponent(getBookingFormFormat(checkOutDate))}`;
                
                let iframe = document.getElementById("availabilityIframe");
                let modal = document.getElementById("availabilityModal");

                if (!iframe || !modal) {
                    console.error("Required DOM elements are not found.");
                    return null;
                }

                iframe.src = nb_bookingUrl;
                modal.style.display = "block";
                
                return nb_bookingUrl;
            } catch (error) {
                console.error("An error occurred while checking availability:", error);
                return null;
            }
        }

        /**
         * @function updateElementText
         * @description Update the text content of a given DOM element.
         * @param {string} element The ID of the DOM element to update.
         * @param {string} text The text to update the element with.
         * @param {boolean} [parent=false] If true, update the parent element of the given element instead of the given element itself.
         */
        function updateElementText(element, text, parent = false) {
            try {
                const domElement = document.getElementById(element);
                if (!domElement) {
                    console.error(`Element with ID '${element}' not found.`);
                    return;
                }
                const targetElement = parent ? domElement.parentElement : domElement;
                if (!targetElement) {
                    console.error(`Parent element for ID '${element}' not found.`);
                    return;
                }
                targetElement.innerText = text;
            } catch (error) {
                console.error("An error occurred while updating the element text:", error);
            }
        }

        /**
         * @function updateElementValue
         * @description Update the value of a given form element.
         * @param {string} element The ID of the form element to update.
         * @param {string} text The value to update the element with.
         */
        function updateElementValue(element, text) {
            try {
                const domElement = document.getElementById(element);
                if (!domElement) {
                    console.error(`Element with ID '${element}' not found.`);
                    return;
                }
                domElement.value = text;
            } catch (error) {
                console.error("An error occurred while updating the element value:", error);
            }
        }

        /**
         * @function getElementValue
         * @description Get the value of a given form element.
         * @param {string} element The ID of the form element to get the value of.
         * @returns {string} The value of the given form element.
         */
        function getElementValue(element) {
            try {
                const domElement = document.getElementById(element);
                if (!domElement) {
                    console.error(`Element with ID '${element}' not found.`);
                    return "";
                }
                return domElement.value;
            } catch (error) {
                console.error("An error occurred while getting the element value:", error);
                return "";
            }
        }

        /**
         * @function getCustomDateFormat
         * @description Convert a date object to a date string in the format "DD-MMM-YYYY".
         * @param {Date} date The date object to convert.
         * @returns {string} The converted date string in the format "DD-MMM-YYYY".
         */
        function getCustomDateFormat(date) {
            if (!date) {
                console.error("Date object provided is null or undefined.");
                return "";
            }
            try {
                let dateArray = date.toLocaleString(nb.config.language).slice(0, 10).split("/"),
                    dateString = date.toString(nb.config.language).slice(0, 10).split(" ");
                return dateString[2] + "-" + dateString[1] + "-" + dateArray[2];
            } catch (error) {
                console.error("An error occurred while converting the date:", error);
                return "";
            }
        }

        /**
         * @function getBookingFormFormat
         * @description Convert a date object to a date string in the format "YYYY-MM-DD".
         * @param {Date} date The date object to convert.
         * @returns {string} The converted date string in the format "YYYY-MM-DD".
         */
        function getBookingFormFormat(date) {
            if (!date) {
                console.error("Date object provided is null or undefined.");
                return "";
            }
            try {
                const options = { year: 'numeric', day: '2-digit', month: '2-digit' };
                const dateFormatted = date.toLocaleString(nb.config.language, options).replace(/\//g, '-');
                const dateString = dateFormatted.split("-");
                return dateString[2] + "-" + dateString[1] + "-" + dateString[0];
            } catch (error) {
                console.error("An error occurred while converting the date:", error);
                return "";
            }
        }

        /**
         * @function dateDiff
         * @description Calculate the difference between two given dates in days.
         * @param {Date} date1 The first date to compare.
         * @param {Date} date2 The second date to compare.
         * @param {boolean} [sign=false] If true, return a signed difference (positive if date1 is after date2, negative if date1 is before date2). If false, always return a positive difference.
         * @returns {number} The difference between the two given dates in days.
         */
        function dateDiff(date1, date2, sign = false) {
            if (!date1 || !date2) {
                console.error("Date objects provided are null or undefined.");
                return 0;
            }

            if (!(date1 instanceof Date) || !(date2 instanceof Date)) {
                console.error("Date objects provided are not instances of Date.");
                return 0;
            }

            try {
                const diff_ms = date1.getTime() - date2.getTime();
                const min = 60,
                    hour = 24,
                    sec = 60,
                    milli = 1000;
                let diff = Math.floor(((diff_ms / milli) / sec / min / hour));
                return sign ? diff : Math.abs(diff);
            } catch (error) {
                console.error("An error occurred while calculating the difference between the two given dates:", error);
                return 0;
            }
        }

        /**
         * @function daysInMs
         * @description Convert a number of days into milliseconds.
         * @param {number} numberOfDays The number of days to convert.
         * @returns {number} The number of milliseconds equivalent to the given number of days, or 0 if input is invalid.
         */
        function daysInMs(numberOfDays) {
            if (typeof numberOfDays !== 'number' || isNaN(numberOfDays)) {
                console.error("Invalid input: numberOfDays must be a valid number.");
                return 0;
            }

            let min = 60,
                hour = 24,
                sec = 60,
                milli = 1000;
            try {
                return numberOfDays * hour * min * sec * milli;
            } catch (error) {
                console.error("An error occurred while converting days to milliseconds:", error);
                return 0;
            }
        }

        document.addEventListener("readystatechange", () => {
            if (!nb.config.initialised) {
                nb.config.initialised = true;
                initWidget(nb.config.initialised);
            }
        });

        /**
         * @function initWidget
         * @description Initialises the NightsBridge Date Widget by initialising the Check In and Check Out date pickers, and adding event listeners to the Check Availability button and the window.
         * @param {boolean} initialised Whether the widget has been initialised before.
         */
        let initWidget = function (initialised) {
            try {
                const checkInElement = document.getElementById("nb_CheckInDate");
                const checkOutElement = document.getElementById("nb_CheckOutDate");
                const checkAvailabilityBtn = document.getElementById("nb_checkAvailabilityBtn");
                const closeBtn = document.querySelector(".close-btn");
                const availabilityModal = document.getElementById("availabilityModal");
                
                if (!checkInElement || !checkOutElement || !checkAvailabilityBtn || !closeBtn || !availabilityModal) {
                    throw new Error("One or more required DOM elements are not found.");
                }

                nb.nb_DateSelection.nb_CheckInField_DatePicker = checkInElement.flatpickr({
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
                            const formattedDate = getCustomDateFormat(checkOutDate);
                            nb.nb_DateSelection.nb_CheckOutField_DatePicker.setDate(formattedDate, true, nb.config.customFormat);
                            document.querySelector("#nb_CheckOutDate+input").value = formattedDate;
                        } else {
                            durationOfStay = dateDiff(checkInDate, checkOutDate);
                        }
                    }
                });

                nb.nb_DateSelection.nb_CheckOutField_DatePicker = checkOutElement.flatpickr({
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

                checkAvailabilityBtn.addEventListener("click", function () {
                    nb_CheckAvailabilityOnBookingForm();
                });

                closeBtn.addEventListener("click", function () {
                    availabilityModal.style.display = "none";
                });

                /**
                 * Hides the availability modal when a click occurs outside the modal
                 * @param {Event} event - The click event
                 */
                window.onclick = function (event) {
                    try {
                        if (event && event.target && availabilityModal && event.target === availabilityModal) {
                            availabilityModal.style.display = "none";
                        }
                    } catch (error) {
                        console.error("An error occurred while trying to hide the availability modal:", error);
                    }
                };
            } catch (error) {
                console.error("An error occurred while initializing the widget:", error);
            }
        };

        return {
            initWidget: initWidget
        };

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('availabilityModal');
    var btn = document.getElementById('nb_checkAvailabilityBtn');
    var span = document.getElementsByClassName('close-btn')[0];

/**
 * @function onclick
 * @description Opens the NightsBridge availability modal when the Check Availability button is clicked.
 */
    btn.onclick = function() {
        modal.style.display = 'block';
    }

/**
 * @function onclick
 * @description Hides the NightsBridge availability modal when the 'X' button is clicked.
 */
    span.onclick = function() {
        modal.style.display = 'none';
    }

/**
 * @function onclick
 * @description Hides the NightsBridge availability modal when the user clicks anywhere outside the modal.
 */
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});


















    }(window.nb);
});