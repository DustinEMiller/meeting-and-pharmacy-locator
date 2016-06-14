// TODO: LINK UP TO SOCIAL MEDIA/GOOGLE PLACES TO ADD HOURS/PICTURES OF BUSINESS
// Has tight coupling with LocationManager.
var MapManager = (function(){
    function codeAddress(next){
        var location,
            geoAddress = toTitleCase(places[nextAddress].address) + ',' + 
                toTitleCase(places[nextAddress].city) + ',' + places[nextAddress].state + ',' 
                + places[nextAddress].zip,
            templateScript,
            number = nextAddress + 1

        if($mapform.data('map-type') === 'event') {
            location = toTitleCase(places[nextAddress].venueName) + ' ' + toTitleCase(places[nextAddress].address) + ' ' + 
                toTitleCase(places[nextAddress].city) + ' ' + places[nextAddress].state + ' ' 
                + places[nextAddress].zip;
        } else {
            location = toTitleCase(places[nextAddress].name) + ' ' + toTitleCase(places[nextAddress].address) + ' ' + 
                toTitleCase(places[nextAddress].city) + ' ' + places[nextAddress].state + ' ' 
                + places[nextAddress].zip    
        }

        geocoder.geocode( { address: geoAddress }, function(results, status) {
                     
        if (status === google.maps.GeocoderStatus.OK) {

            if(!mobile){
               map.setCenter(results[0].geometry.location);

                var iconImage = {
                    url: '//www.healthalliance.org/marker'+number+'.png',
                    size: new google.maps.Size(32, 32),
                    origin: new google.maps.Point(0,0),
                    anchor: new google.maps.Point(10, 33)
                };

                var finalLatLng = results[0].geometry.location;

                if (allMarkers.length !== 0) {
                    allMarkers.map(function(row, index){
                        if (finalLatLng.equals(row.getPosition())) {
                            //update the position of the coincident marker by applying a small multipler to its coordinates
                            var newLat = finalLatLng.lat() + (Math.random() - 1.5) / 1500;
                            var newLng = finalLatLng.lng() + (Math.random() - 1.5) / 1500;
                            finalLatLng = new google.maps.LatLng(newLat,newLng);
                        }
                    });
                }

                var marker = new google.maps.Marker({
                    map: map,
                    position: finalLatLng,
                    icon:iconImage,
                    title:location,
                    optimized: false,
                    zIndex: (nextAddress + 1),
                    index: nextAddress
                });

                google.maps.event.addListener(marker, 'click', function() {
                    var evt = { 
                        marker: true,
                        dataIndex: this.index
                    };
                    handleDirections(evt);    
                });

                allMarkers.push(marker);

                var bounds = new google.maps.LatLngBounds();
                
                allMarkers.map(function(row){
                    bounds.extend(row.getPosition());
                });
                map.setCenter(bounds.getCenter());
                map.fitBounds(bounds);
                templateScript = $('#location-list').html();
            }
            else {
                templateScript = $('#location-list-mobile').html();
            }

            var template = Handlebars.compile(templateScript);

            switch ($mapform.data('map-type')) {
                case 'pharmacy':
                    var fax = places[nextAddress].fax,
                        phone = places[nextAddress].phone;
                    //TODO: Length tests are just temporary until better data is pulled in for the numbers. Tired of keeping functionality in dev while I wait for stuff.
                    if (fax) {
                        fax = fax.replace(/(\r\n|\n|\r)/gm,"");
                        if(fax.length === 10) {
                            fax = null;
                        }
                    }

                    if(phone && phone.length === 10) {
                        phone = null;
                    }

                    var context = {
                        index: nextAddress,
                        number: number,
                        name: toTitleCase(places[nextAddress].name),
                        address: toTitleCase(places[nextAddress].address),
                        address_2: toTitleCase(places[nextAddress].address_2),
                        city: toTitleCase(places[nextAddress].city),
                        state: places[nextAddress].state,
                        zip: places[nextAddress].zip,
                        nabp: places[nextAddress].nabp,
                        phone: phone,
                        fax: fax
                    };
                    break;
                case 'event': 
                    var context = {
                        index: nextAddress,
                        number: number,
                        name: toTitleCase(places[nextAddress].name),
                        address: toTitleCase(places[nextAddress].address),
                        city: toTitleCase(places[nextAddress].city),
                        state: places[nextAddress].state,
                        zip: places[nextAddress].zip,
                        venueName: toTitleCase(places[nextAddress].venueName),
                        date: places[nextAddress].date,
                        name: toTitleCase(places[nextAddress].name),
                        roomName: toTitleCase(places[nextAddress].roomName),
                        startTime: places[nextAddress].startTime,
                        endTime: places[nextAddress].endTime
                    };

                    break;
                case 'seminar':  
                    var context = {
                        index: nextAddress,
                        number: number,
                        name: toTitleCase(places[nextAddress].name),
                        address: toTitleCase(places[nextAddress].address),
                        city: toTitleCase(places[nextAddress].city),
                        state: places[nextAddress].state,
                        zip: places[nextAddress].zip,
                        events: []
                    };

                    places[nextAddress].events.sort(function(a,b){
                        return new Date(a.fullDate).getTime() - new Date(b.fullDate).getTime();
                    });

                    context.events = places[nextAddress].events.map(function(row,index){
                        var event = {
                            number: index+1,
                            date: row.date,
                            time: row.time,
                            room: row.room,
                            campaignId: row.campaignId,
                            campaignName: row.campaignName,
                            placeIndex: row.placeIndex
                        };
                        return event;
                    });
                    break;
            }

            if(mobile){
                var root;
                if($mapform.data('map-type') === 'pharmacy') {
                    root = 'maps.google.com?saddr='+origin+'&daddr='+context.name+' '+context.address+' '+context.city+' '+context.state;
                }
                else {
                    root = 'maps.google.com?saddr='+origin+'&daddr='+context.address+' '+context.city+' '+context.state;
                }

                context.walk = encodeURI(root+'&directionsmode=walk');
                context.bike = encodeURI(root+'&directionsmode=bike');
                context.transit = encodeURI(root+'&directionsmode=transit');
                context.drive = encodeURI(root+'&directionsmode=drive');
            }

            $locations.append(template(context));

        } else {
            if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                nextAddress--;
                delay+=1;
              } else {
                messageHandler("There was an external error, please try again", '#number-results', 'error', true);
            } 
        }
        nextAddress+=1;
        next();
      });
    }

    function queryHandler(results) {
        switch ($mapform.data('map-type')) {
            case 'pharmacy':
                places = results.map(function(row){
                    var phone = '', fax = '';
                    if (row.phone !== null){
                        phone = row.phone;
                    }
                    if (row.fax !== null){
                        fax = row.fax;
                    }
                    var place = {
                        nabp: row.nabp,
                        npi: row.npi,
                        name: row.pharmacy_name,
                        address: row.address,
                        address_2: row.address_2,
                        city: row.city,
                        state: row.state,
                        zip: row.zip,
                        phone: phone,
                        fax: fax
                    };
                    return place;
                });
                var context = {
                    number: places.length
                };
                break;
            case 'event':
                places = results.filter(function(row){
                    var eventDate = new Date(row.start_time),
                        now = new Date();

                    if(Date.parse(now.toDateString()) > Date.parse(eventDate.toDateString())){
                        return false;
                    }
                    return true;
                }).map(function(row){
                    var startTime = new Date(row.start_time),
                        endTime = new Date(row.end_time),
                        roomName = '';

                    if (row.room_name !== null){
                        roomName = row.room_name;
                    }
                  
                    var place = {
                        name: row.event_name,
                        venueName: row.venue_name,
                        address: row.address,
                        city: row.city,
                        state: row.state,
                        zip: row.zip,
                        date: startTime.toDateString(),
                        startTime: startTime.toLocaleTimeString(navigator.language, {hour: '2-digit', minute:'2-digit'}),
                        endTime: endTime.toLocaleTimeString(navigator.language, {hour: '2-digit', minute:'2-digit'}),
                        roomName: roomName,
                        fullDate: row.start_time
                    };

                    return place
                });

                var results = places.length + " Events Found";

                if(places.length === 0){
                    results = "No Events Found";
                }
                else if (places.length === 1) {
                    results = places.length + " Event Found";
                }

                var context = {
                    results: results
                };

                places.sort(function(a,b){
                    return new Date(a.fullDate) - new Date(b.fullDate);
                });

                break;
            case 'seminar':
                gkvIframeInsert();
                var seminars = 0;
                
                results.filter(function(row){
                    var t = (row.start_date + ' ' + convertToMilitary(row.time)).split(/[- :]/);
                    var semDate = new Date(t[0], t[1]-1, t[2], t[3], t[4], '00'),
                        now = new Date();

                    if(Date.parse(now.toDateString()) > Date.parse(semDate.toDateString())){
                        return false;
                    }
                    return true;
                }).map(function(row){
                    var t = (row.start_date + ' ' + convertToMilitary(row.time)).split(/[- :]/);
                    var semDate = new Date(t[0], t[1]-1, t[2], t[3], t[4], '00'),
                    room = '';

                    if (row.address2 !== null){
                        room = row.address2;
                    }

                    var idx = getKeyByAddress(places, row.address);

                    var index = idx >= 0 ? idx : places.length;
                    
                    var event = {
                        date: semDate.toDateString(),
                        time: semDate.toLocaleTimeString(navigator.language, {hour: '2-digit', minute:'2-digit'}),
                        fullDate: semDate,
                        room: room,
                        campaignId: row.campaign_id,
                        campaignName: row.campaign_name,
                        placeIndex: index
                    };

                    var idx = getKeyByAddress(places, row.address);

                    if(idx !== -1) {
                        places[idx].events.push(event);
                        seminars+=1;
                    }
                    else {
                        var place = {
                            name: row.location,
                            address: row.address,
                            city: row.city,
                            state: row.state,
                            zip: row.zip,
                            events:[]
                        };
                        place.events.push(event);
                        seminars+=1;
                        places.push(place);
                    }
                });

                var msgLoc = " at " + places.length+" Different Locations";

                if(places.length === 0) {
                    msgLoc = "";
                }
                else if (places.length === 1) {
                    msgLoc = " at 1 Location";
                }

                var context = {
                    locations: msgLoc,
                    number: seminars
                };
                break;
        }    

        var templateScript = $('#result-list').html(),
            template = Handlebars.compile(templateScript);

        if(!mobile) {
            $('#loading').toggle();
        }

        $(template(context)).insertAfter('#loading');

        theNext();
    }

    function convertToMilitary(time) {
        var hours = parseInt(time.substr(0, 2));
        if((time.indexOf('am') != -1 || time.indexOf('AM') != -1) && hours == 12) {
            time = time.replace('12', '0');
        }
        if((time.indexOf('pm')  != -1 || time.indexOf('PM') != -1) && hours < 12) {
            time = time.replace(hours, (hours + 12));
        }
        return time.replace(/(am|pm|AM|PM)/, '');
    }

    function theNext() {

        if (nextAddress < places.length) {
            if (nextAddress < 10){
                delay = 100;
            }
            //This is a no no. Find a better solution.
            //Maximum call stack error
            setTimeout('MapManager.codeAddress(MapManager.theNext)', delay);  
        } else {
            loadingHandler();
        }
    }

    function requestDirections(type, locationID, caller) { 
        var statusMessage = '',
            element = 'none',
            end = allMarkers[locationID].title,
            request = {};

        if($('[name=starting-location]').length > 0) {
            origin = $('[name=starting-location]').val();
        } 

        //TODO:Clean
        switch (type) {
            case 'walk':
                type = google.maps.TravelMode.WALKING;
                break;
            case 'bike':
                type = google.maps.TravelMode.BICYCLING;
                break;
            case 'transit':
                type = google.maps.TravelMode.TRANSIT;
                break;
            case 'drive':
                type = google.maps.TravelMode.DRIVING;
                break;   
        }

        request = {
          origin:origin,
          destination:end,
          travelMode: type
        };

        directionsService.route(request, function(result, status) {

            if (status === google.maps.DirectionsStatus.OK) {
                $directions.html('');
                directionsDisplay.setDirections(result);
                showSteps(result);
            }
            else {
                switch (status) {
                    case google.maps.DirectionsStatus.NOT_FOUND:
                        statusMessage = "We could not find that location. Make sure your search is spelled correctly.";
                        break;
                    case google.maps.DirectionsStatus.ZERO_RESULTS:
                        statusMessage = "There are no available routes between the origin and destination. Please try another transit mode.";
                        break;
                    case google.maps.DirectionsStatus.INVALID_REQUEST:
                        statusMessage = "There was an issue with the request. Please try another request.";
                        break; 
                    case google.maps.DirectionsStatus.OVER_QUERY_LIMIT:
                        statusMessage = "There have been too many recent requests. Please wait a few minutes and try again";
                        break; 
                    default:
                        statusMessage = "There was an error retrieving directions. Please try again.";
                        break;
                }
                var isForm = false;
                if(caller === 'starting-loc') {
                    element = '[name=starting-location]';
                    isForm = true;
                }
                else {
                    element = '#filter';
                }
                messageHandler(statusMessage, element, 'error', isForm);
            }
        });
    }

    function showSteps(directionResult) {
        var route = directionResult.routes[0].legs[0],
            templateScript = $('#direction-steps').html(),
            template = Handlebars.compile(templateScript),
            context = {
                origin: origin,
                distance: route.distance.text,
                duration: route.duration.text,
                steps: []
            };

        route.steps.map(function(row){
            var step = {
                instructions: row.instructions,
                distance: row.distance.text,
                duration: row.duration.text
            };
            context.steps.push(step);
        });


      $directions.append(template(context));
      messageHandler(directionResult.routes[0].warnings, '#back-locations', 'warning', false);
    }

    function gkvIframeInsert() {
        var tag_url="http://fls.doubleclick.net/activityi;src=5218519;type=age-i0;cat=finda00;ord=1;num="+Math.floor(Math.random()*999999)+"?";
        if(document.getElementById("DCLK_FLDiv")){var flDiv=document.getElementById("DCLK_FLDiv");}
        else{var flDiv=document.body.appendChild(document.createElement("div"));flDiv.id="DCLK_FLDiv";flDiv.style.display="none";}
        var DCLK_FLIframe=document.createElement("iframe");
        DCLK_FLIframe.id="DCLK_FLIframe_"+Math.floor(Math.random()*999999);
        DCLK_FLIframe.src=tag_url;
        flDiv.appendChild(DCLK_FLIframe); 
    }

    function loadingHandler() {
        submitState.disabled = !submitState.disabled;
        if (submitState.disabled) {
            $submitButton.attr('value', 'Loading');
            $submitButton.attr('role', 'alert');
            $submitButton.css('width', submitState.width);   
            $submitButton.addClass('gray-pulse');    
        } else {
            $submitButton.attr('value', submitState.label);
            $submitButton.css('width', 'auto');
            $submitButton.removeAttr('role');
            $submitButton.removeClass('gray-pulse'); 
        }

        $submitButton.prop('disabled', submitState.disabled);
    }

    function isInt(value) {
      if (isNaN(value)) {
        return false;
      }
      var x = parseFloat(value);
      return (x | 0) === x;
    }

    function getKeyByAddress(obj, str) {
        var returnKey = -1;

        $.each(obj, function(key, info) {
            if (info.address === str) {
               returnKey = key;
               return false; 
            }   
        });
        return returnKey;       
    }

    function toTitleCase(str) {
        if(str) {
            return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
        }
    }

    function messageHandler(message, selector, messageType, isForm) {  
        if (selector === $mapform || selector === '[name=location]') {
            loadingHandler();
            if(!mobile) {
                $('#loading').toggle();
            }
        }

        if (isForm) {
            $results.append("<div class='message message-label "+messageType+"' role='alert'>"+message+"</div>");
            if (selector !== $mapform ) {
                $(selector).parent().parent().addClass(messageType);
            }
            $(selector).attr('aria-invalid', 'true');
        }
        else {
            $("<div class='message message-label default-message-box "+messageType+"' role='alert'>"+message+"</div>").insertAfter(selector);
        }
    }
    
    function setMobileStatus() {
        mobile = $mapwrapper.css('display') === 'none' ? true : false;
    }
    
    function handleMapForm(evt) {
        loadingHandler();
        if(!mobile) {
            $('#loading').toggle();
        }

        var radius = $("#radius option:selected").val(),
            location = $('[name=location]').val(),
            url = '//askshirley.org/zip/api/',
            locationType = 'meeting/'+$mapform.data('map-type')+'/',
            searchArguments,
            endpoint = 'zipcode/';

        origin = location;
        nextAddress = 0;
        delay = 0;

        allMarkers.map(function(row){
            row.setMap(null);
        });
        
        allMarkers = []; 
        places = [];

        $directions.html("");
        $locations.html("");
        $directions.css("right", "100%");
        $locations.show();  
        $('#finished h2, .message, #number-results').remove();
        $('.error, .warning').removeClass('error warning');
        $('[aria-invalid=true]').attr('aria-invalid', 'false');


        if($mapform.data('map-type') === 'pharmacy' && !$("#pharmacy option:selected").val()) {
            messageHandler("Please select a pharmacy type.", $mapform, 'error', true);
            return;
        }

        if (!radius){
            messageHandler("Please select a radius.", $mapform, 'error', true);
            return;
        }

        if(location === ''){
            messageHandler("That is not a valid ZIP or City, State combo", '[name=location]', 'error', true);
            return;
        } else if (isInt(location)) {
            if(/(^\d{5}$)/.test(location)) {
                searchArguments = location+'/'+radius;
            } 
            else {
                messageHandler("That is not a valid zipcode", '[name=location]', 'error', true);
                return;
            }
        } else {
            location = location.split(',');

            if(location.length !== 2){
                messageHandler("That is not a valid City, State combo", '[name=location]', 'error', true);
                return;
            }

            location = location.map(function(row){
                return row.trim();
            });
        
            var state = states.filter(function ( obj ) {
                if (obj.name.toLowerCase() === location[1].toLowerCase() || 
                        obj.abbreviation.toLowerCase() === location[1].toLowerCase()) {
                    return obj;
                }
            })[0];

            if(!state && location[1].length === 2) {
                messageHandler("Incorrect State Abbreviation", '[name=location]', 'error', true);
                return;
            }
            else if(!state) {
                messageHandler("Incorrect State Name", '[name=location]', 'error', true);
                return;
            }

            location[1] = state.abbreviation.toUpperCase();
            searchArguments = location[0]+'/'+location[1]+'/'+radius;
            endpoint = 'cityState/';
        }

        if($mapform.data('map-type') === 'pharmacy') {
            locationType = 'pharmacy/'+$("#pharmacy option:selected").val()+'/';
        }

        $.ajax({
            url: url+endpoint+clientKey+'/'+locationType+searchArguments,
            dataType: 'text',
            complete: function(jqXHR, status){
                switch (status) {
                    case 'success':
                        var data = JSON.parse(jqXHR.responseText);

                        if(data.error || data.error_msg){
                            messageHandler("There was a problem with your request. Please try again later", $mapform, 'error', true);
                        }
                        else if(data.results.length > 0) {
                            queryHandler(data.results);
                        }
                        else {
                            messageHandler("There was no data for your location. Please try a different city/state combination.", $mapform, 'error', true);
                        }
                        break;
                    case 'timeout':
                        messageHandler("The request took to long to complete. Please try again.", $mapform, 'error', true);
                        break;
                    case 'nocontent':
                        messageHandler("There was no content to process. Please try again.", $mapform, 'error', true);
                        break;
                    default:
                        messageHandler("There was an error. Please try again.", $mapform, 'error', true);
                        break;
                } 
            }
        });
    }

    function handleResultsLocation(evt) {
        $('.default-message-box').remove();
        $('.message').remove();
        var type = $('.direction-links .button.active').attr('class').replace(' active', '').replace(' button', '');
        requestDirections(type, $('.direction-links .button.active').parent().attr('data-marker-index'), 'starting-loc');
    }
    
    function handleDirections(evt) {
        if(!mobile){
            var type,
                index;

            $('.default-message-box').remove();

            if (evt.marker) {
                type = 'drive';
                index = evt.dataIndex;
            } else {
                type = $(evt.currentTarget).attr('class').replace(' active', '').replace(' button', '');
                index = $(evt.currentTarget).parent().attr('data-marker-index');
                $('.direction-links .button.active').removeClass('active');
                $(evt.currentTarget).addClass('active');
            }

            $directions.animate({right: 0}, 300, function() {
                $locations.hide();   
            });
            
            requestDirections(type, index, 'directions-button');
        }
    }

    function handleRegister(evt) {
        $('#formModal .row').html('');
        var template = Handlebars.compile($('#register-form').html()),
            context,
        placeI = $(evt.target).attr('data-place-index'),
        eventI = $(evt.target).attr('data-event-index');

        var context = {
            campaignId: places[placeI].events[eventI].campaignId,
            address:places[placeI].address,
            city:places[placeI].city,
            state:places[placeI].state,
            zip:places[placeI].zip,
            campaignName: places[placeI].events[eventI].campaignName,
            date:places[placeI].events[eventI].date,
            time:places[placeI].events[eventI].time,
            room:places[placeI].events[eventI].room,
            name:places[placeI].name
        } 

        $('#formModal .row').append(template(context));
        $('#formModal').foundation('reveal', 'open');
        $registrationForm = $(registrationForm);

        $.validator.addMethod("dateFormat",function ValidateCustomDate(d, e) {
            var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(d);

            if (d === '') {
                return true;
            }

            if (!match) {
                // pattern matching failed hence the date is syntactically incorrect
                return false;
            }
            var month = parseInt(match[1], 10) - 1; // months are 0-11, not 1-12
            var day   = parseInt(match[2], 10);
            var year  = parseInt(match[3], 10);
            var date  = new Date(year, month, day);
            // now, Date() will happily accept invalid values and convert them to valid ones
            // therefore you should compare input month/day/year with generated month/day/year
            return date.getDate() == day && date.getMonth() == month && date.getFullYear() == year;
        },"Please enter a date in the format MM/DD/YYYY.");

        $.validator.addMethod("withTwoStrings",function(value, element) {
            howManyWords = value.trim();
            howManyWords = howManyWords.replace(/\s{2,}/g, ' '); //remove extra spaces
            howManyWords = howManyWords.split(' ');

            if(howManyWords.length == 2){
                return true;
            }
            else{
                return false;
            }
        e.preventDefault();
        },"Please Include First and Last Name.");

        $registrationForm.validate({
            rules: {
                name: {
                    required:true,
                    withTwoStrings:true
                },
                email: {
                    email:true
                },
                phoneNumber: {
                    phoneUS: true,
                    required: true
                },
                city: "required",
                state: {
                    required:true,
                    stateUS:true       
                },
                zip: {
                    required:true,
                    zipcodeUS:true       
                },
                birthday: {
                    dateFormat:true
                },
                attendees: {
                    number: true
                }
            }
        });

        $registrationForm.on('submit',handleEventRegistration.bind(this));
    }

    function handleEventRegistration(evt) {
        evt.preventDefault();

        if($registrationForm.valid()) {
            $('#form-loading').toggle();
            $('#form-wrapper').hide();
            $.ajax({
                url: $registrationForm.attr('action'),
                type: 'POST',
                data: $registrationForm.serialize()
            }).always(function (data, status, response) {
                $('#form-loading').toggle();
                if(response.status < 400) {
                    $('#form-message-title').text('Thank You');
                    $('#form-message').text('Your registration was submitted successfully.');
                } else {
                    $('#form-message-title').text('Error');
                    $('#form-message').text('We\'re sorry, but there was an error with your submission. Please try again later.');      
                }
                $('#form-message-wrapper').toggle();
                $('html, body').animate({scrollTop:$('#formModal').position().top}, 'slow');
            });    
        }   
    }

    function handleBacklink(evt) {
        $locations.show();
        $directions.animate({right: '100%'}, 300);
    }
    
    function loadStates(data) {
        states = data;
    }

    function getMobileStatus() {
        return mobile;;
    }

    function getSubmitButton() {
        return $submitButton;
    }
    
    function init(opts) {
        
        $mapform = $(opts.mapform);
        $results = $(opts.results);
        $locations = $(opts.locations);
        $mapwrapper = $(opts.mapwrapper);
        $directions = $(opts.directions);
        $submitButton = $(opts.submitButton);
        registrationForm = opts.registrationForm

        submitState.label = $submitButton.attr('value');
        submitState.width = $submitButton.innerWidth();
        submitState.disabled = false;

        loaded = true;

        columnHeight = document.documentElement.clientHeight - ($('header').outerHeight(true) + parseInt($("main").css("padding-bottom")) + 
            $('#menu-bar').outerHeight(true) + $('#type-title').outerHeight(true) + $('#type-form').outerHeight(true));
        columnHeight = columnHeight < 450 ? 450 : columnHeight;
        
        if ($mapwrapper.css('display') !== 'none') {
            $('#map-canvas, #results').css("height", columnHeight);    
            google.maps.event.trigger(map, "resize");
        }

        setMobileStatus();

        if('XDomainRequest' in window && window.XDomainRequest !== null) {
            $.support.cors = true;
        }

        //listen for map form submit
        $mapform.bind('submit',handleMapForm.bind(this));

        //listen for clicks on starting location button
        $results.on('click touch','#starting-loc button',handleResultsLocation.bind(this));
        
        //listen for clicks on direction buttons
        $locations.on('click touch','.direction-links .button',handleDirections.bind(this)); 

        //listen for clicks on register button
        $locations.on('click touch','.register.button',handleRegister.bind(this)); 

        //listen for clicks on back link
        $directions.on('click touch','#back-locations',handleBacklink.bind(this)); 

        $(publicAPI).trigger("loaded");
    }
    
    var
        states,
        origin,
        columnHeight,
        allMarkers = [],
        nextAddress = 0,
        submitState = {},
        delay = 0,
        places = [],
        geocoder = new google.maps.Geocoder(),
        map = new google.maps.Map(document.getElementById('map-canvas'), {
            center: new google.maps.LatLng(34.9983818,-99.99967040000001),
            zoom: 4,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }),
        directionsService = new google.maps.DirectionsService(),
        directionsDisplay = new google.maps.DirectionsRenderer({
            map: map
        }),
        clientKey = 'pD5ltovTQHNvhP32e2zQ',
        mobile = false,
        loaded = false,
        registrationForm,
        
        //Dom References
        $mapform,
        $results,
        $locations,
        $mapwrapper,
        $directions,
        $submitButton,
        $registrationForm,
        
        publicAPI = {
            //I really don't want to expose these 3 methods to the public space, 
            //but I can't think of a way to get around max call stack errors or 
            //JSONP calls
            codeAddress: codeAddress,
            theNext: theNext,
            setMobileStatus: setMobileStatus,
            getMobileStatus: getMobileStatus,
            getSubmitButton: getSubmitButton,
            loaded: loaded,
            loadStates: loadStates,
            init: init
        }
    ;
    
    return publicAPI;
})();

MapManager.loadStates([{name:'Alabama',abbreviation:'AL'},{name:'Alaska',abbreviation:'AK'},{name:'American Samoa',abbreviation:'AS'},{name:'Arizona',abbreviation:'AZ'},{name:'Arkansas',abbreviation:'AR'},{name:'California',abbreviation:'CA'},{name:'Colorado',abbreviation:'CO'},{name:'Connecticut',abbreviation:'CT'},{name:'Delaware',abbreviation:'DE'},{name:'District Of Columbia',abbreviation:'DC'},{name:'Federated States Of Micronesia',abbreviation:'FM'},{name:'Florida',abbreviation:'FL'},{name:'Georgia',abbreviation:'GA'},{name:'Guam',abbreviation:'GU'},{name:'Hawaii',abbreviation:'HI'},{name:'Idaho',abbreviation:'ID'},{name:'Illinois',abbreviation:'IL'},{name:'Indiana',abbreviation:'IN'},{name:'Iowa',abbreviation:'IA'},{name:'Kansas',abbreviation:'KS'},{name:'Kentucky',abbreviation:'KY'},{name:'Louisiana',abbreviation:'LA'},{name:'Maine',abbreviation:'ME'},{name:'Marshall Islands',abbreviation:'MH'},{name:'Maryland',abbreviation:'MD'},{name:'Massachusetts',abbreviation:'MA'},{name:'Michigan',abbreviation:'MI'},{name:'Minnesota',abbreviation:'MN'},{name:'Mississippi',abbreviation:'MS'},{name:'Missouri',abbreviation:'MO'},{name:'Montana',abbreviation:'MT'},{name:'Nebraska',abbreviation:'NE'},{name:'Nevada',abbreviation:'NV'},{name:'New Hampshire',abbreviation:'NH'},{name:'New Jersey',abbreviation:'NJ'},{name:'New Mexico',abbreviation:'NM'},{name:'New York',abbreviation:'NY'},{name:'North Carolina',abbreviation:'NC'},{name:'North Dakota',abbreviation:'ND'},{name:'Northern Mariana Islands',abbreviation:'MP'},{name:'Ohio',abbreviation:'OH'},{name:'Oklahoma',abbreviation:'OK'},{name:'Oregon',abbreviation:'OR'},{name:'Palau',abbreviation:'PW'},{name:'Pennsylvania',abbreviation:'PA'},{name:'Puerto Rico',abbreviation:'PR'},{name:'Rhode Island',abbreviation:'RI'},{name:'South Carolina',abbreviation:'SC'},{name:'South Dakota',abbreviation:'SD'},{name:'Tennessee',abbreviation:'TN'},{name:'Texas',abbreviation:'TX'},{name:'Utah',abbreviation:'UT'},{name:'Vermont',abbreviation:'VT'},{name:'Virgin Islands',abbreviation:'VI'},{name:'Virginia',abbreviation:'VA'},{name:'Washington',abbreviation:'WA'},{name:'West Virginia',abbreviation:'WV'},{name:'Wisconsin',abbreviation:'WI'},{name:'Wyoming',abbreviation:'WY'}]);

//This module is not packaged with the other code in this file.
$(window).on('load',function(){
    MapManager.init({
        mapform: '#mapform',
        results : '#results',
        locations : '#locations',
        mapwrapper : '#map-wrapper',
        directions: '#directions',
        submitButton: '#filter',
        registrationForm: '#event-registration-request'
    });
});

$(window).on('resize',function(){
    if (MapManager.loaded) {
        MapManager.setMobileStatus();
    }
});