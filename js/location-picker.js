(function () {
  'use strict';

  var locationButtons = document.querySelectorAll('[data-location-button]');

  function getCity(address) {
    return address.city || address.town || address.municipality || address.village || '';
  }

  function getStreetAddress(result) {
    var address = result.address || {};
    var street = [address.house_number, address.road || address.pedestrian || address.residential || address.path]
      .filter(Boolean)
      .join(' ');
    var locality = address.neighbourhood || address.suburb || address.city_district;
    var city = getCity(address);
    var parts = [street, locality, city, address.postcode, address.state].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : (result.display_name || '');
  }

  function normalizeCity(city) {
    return city.toLowerCase().trim().replace(/[\s-]+/g, '');
  }

  locationButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      var citySelect = document.getElementById(button.getAttribute('data-location-button'));
      var addressInput = document.getElementById(button.getAttribute('data-address-target'));
      var addressBox = addressInput ? addressInput.closest('.cr-pickup-address') : null;
      var addressLabel = addressBox ? addressBox.querySelector('[data-address-label]') : null;
      var accuracyMessage = addressBox ? addressBox.querySelector('[data-location-accuracy]') : null;
      var status = document.querySelector(
        '[data-location-status="' + button.getAttribute('data-location-button') + '"]'
      );

      if (!citySelect || !addressInput || !status) {
        return;
      }

      if (!navigator.geolocation) {
        status.textContent = 'Location is not available in this browser. Choose Bengaluru from the list.';
        return;
      }

      button.disabled = true;
      status.textContent = 'Finding your current address…';

      function lookupAddress(position) {
          var coordinates = position.coords;
          var endpoint = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat='
            + encodeURIComponent(coordinates.latitude)
            + '&lon='
            + encodeURIComponent(coordinates.longitude);

          fetch(endpoint, {
            headers: { 'Accept-Language': navigator.language || 'en' }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Location lookup failed');
              }
              return response.json();
            })
            .then(function (result) {
              var detectedCity = getCity(result.address || {});
              var streetAddress = getStreetAddress(result);
              if (!streetAddress) {
                status.textContent = 'We could not find a nearby street address. Enter your pickup address manually.';
                return;
              }

              addressInput.value = streetAddress;
              if (addressBox) {
                addressBox.classList.add('cr-pickup-address--located');
              }
              if (addressLabel) {
                addressLabel.textContent = 'Pickup address found — check or edit';
              }
              if (accuracyMessage && Number.isFinite(coordinates.accuracy) && coordinates.accuracy > 0) {
                accuracyMessage.textContent = 'Device location accuracy is approximately '
                  + Math.round(coordinates.accuracy)
                  + ' metres. Map data may not include your exact house number; verify the address before continuing.';
              }
              var area = (result.address || {}).suburb || (result.address || {}).neighbourhood;
              var isSupported = detectedCity
                && ['bengaluru', 'bangalore'].indexOf(normalizeCity(detectedCity)) !== -1;

              if (isSupported) {
                citySelect.value = 'Bengaluru';
                status.textContent = 'Address found'
                  + (area ? ', near ' + area : '')
                  + '. Check the pickup address; map results may not include your exact house number.';
              } else {
                if (!citySelect.querySelector('option[value=""]')) {
                  citySelect.add(new Option('Choose Bengaluru if that is your trip city', '', true, true), 0);
                }
                citySelect.value = '';
                status.textContent = 'Address found'
                  + (detectedCity ? ' in ' + detectedCity : '')
                  + '. This demo fleet serves Bengaluru only; choose Bengaluru only if that is your trip city.';
              }
            })
            .catch(function () {
              status.textContent = 'We could not look up your city. Choose Bengaluru from the list or try again.';
            })
            .finally(function () {
              button.disabled = false;
            });
      }

      function showLocationError(error) {
        var message;
        if (error.code === error.PERMISSION_DENIED) {
          message = 'Location permission was denied. Allow location for this site in your browser and device settings, then try again.';
        } else if (error.code === error.TIMEOUT) {
          message = 'Your browser could not get a device location before timing out. Check device Location Services or GPS and try again. No address was guessed.';
        } else {
          message = 'Your device could not provide a location. Check Location Services and try again. No address was guessed.';
        }
        status.textContent = message;
        if (addressLabel) {
          addressLabel.textContent = 'Pickup address (optional)';
        }
        if (accuracyMessage) {
          accuracyMessage.textContent = 'No current location was received. You can try again or enter your pickup address manually.';
        }
          button.disabled = false;
      }

      navigator.geolocation.getCurrentPosition(
        lookupAddress,
        function (error) {
          if (error.code === error.PERMISSION_DENIED) {
            showLocationError(error);
            return;
          }

          status.textContent = 'A precise location is taking too long; trying your device’s available location…';
          navigator.geolocation.getCurrentPosition(
            lookupAddress,
            showLocationError,
            { enableHighAccuracy: false, maximumAge: 120000, timeout: 20000 }
          );
        },
        { enableHighAccuracy: true, maximumAge: 30000, timeout: 12000 }
      );
    });
  });

  document.querySelectorAll('[data-pickup-address-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (form.dataset.pickupAddressSaved === 'true') {
        return;
      }

      event.preventDefault();
      var addressInput = form.querySelector('[data-pickup-address]');
      var tokenInput = form.querySelector('[data-pickup-address-csrf]');
      var citySelect = form.querySelector('select[name="city"]');
      var status = form.querySelector('[data-location-status]');
      var submitButtons = form.querySelectorAll('[type="submit"]');

      if (!addressInput || !tokenInput || !tokenInput.value) {
        if (status) {
          status.textContent = 'We could not save the pickup address. Reload the page and try again.';
        }
        return;
      }

      submitButtons.forEach(function (submitButton) {
        submitButton.disabled = true;
      });
      if (status) {
        status.textContent = 'Saving your pickup address for this trip…';
      }

      fetch('./save-pickup-address.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          address: addressInput.value,
          city: citySelect ? citySelect.value : '',
          csrf_token: tokenInput.value
        })
      })
        .then(function (response) {
          return response.json().then(function (result) {
            if (!response.ok || !result.success) {
              throw new Error(result.message || 'Could not save pickup address');
            }
            return result;
          });
        })
        .then(function () {
          form.dataset.pickupAddressSaved = 'true';
          HTMLFormElement.prototype.submit.call(form);
        })
        .catch(function () {
          submitButtons.forEach(function (submitButton) {
            submitButton.disabled = false;
          });
          if (status) {
            status.textContent = 'We could not save the pickup address. Check your connection and try again.';
          }
        });
    });
  });
})();
