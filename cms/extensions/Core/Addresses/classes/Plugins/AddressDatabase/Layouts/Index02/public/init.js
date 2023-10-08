var _IntervalTimer;

$(function() {

    _IntervalTimer = window.setInterval(function() {
        if (typeof map == "object") {

            var bounds = new google.maps.LatLngBounds();

            $.each(addresses, function(index, address) {

                const location = { lat: address.lat, lng: address.lng };

                console.log(location);

                // The marker, positioned at Uluru
                const marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: "Click to zoom",
                });

                marker.addListener("click", () => {
                    var position = $("#address" + address.addressId).offset().top;
                    $("HTML, BODY").animate({
                        scrollTop: position
                    }, 1000);

                });

                bounds.extend(location);
            });

            map.fitBounds(bounds);

            window.clearInterval(_IntervalTimer);
        }
    }, 500);
});
