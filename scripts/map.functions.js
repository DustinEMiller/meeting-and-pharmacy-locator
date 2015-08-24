// TODO: LINK UP TO SOCIAL MEDIA/GOOGLE PLACES TO ADD HOURS/PICTURES OF BUSINESS
// TODO: IE8 DESIGN TWEAKS?
var MapManager = (function(){
    function codeAddress(next){
        var location = toTitleCase(places[nextAddress].name) + ' ' + toTitleCase(places[nextAddress].address) + ' ' + 
                toTitleCase(places[nextAddress].city) + ' ' + places[nextAddress].state + ' ' 
                + places[nextAddress].zip;
        var templateScript;

        var number = nextAddress + 1;
        geocoder.geocode( { address: location }, function(results, status) {

        if (status === google.maps.GeocoderStatus.OK) {

            if(!mobile){
               map.setCenter(results[0].geometry.location);

                var iconImage = {
                    url: '//askshirley.org/images/green/marker'+number+'.png',
                    size: new google.maps.Size(32, 32),
                    origin: new google.maps.Point(0,0),
                    anchor: new google.maps.Point(10, 33)
                };

                var marker = new google.maps.Marker({
                    map: map,
                    position: results[0].geometry.location,
                    icon:iconImage,
                    title:location,
                    optimized: false,
                    zIndex: (nextAddress + 1)
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
                    var context = {
                        index: nextAddress,
                        number: number,
                        name: toTitleCase(places[nextAddress].name),
                        address: toTitleCase(places[nextAddress].address),
                        city: toTitleCase(places[nextAddress].city),
                        state: places[nextAddress].state,
                        zip: places[nextAddress].zip,
                        nabp: places[nextAddress].nabp
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

                    context.events = places[nextAddress].events.map(function(row,index){
                        var event = {
                            number: index+1,
                            date: row.date,
                            time: row.time
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
                messageHandler("There was an external error, please try again", '#number-results', 'error', false);
            } 
        }
        nextAddress+=1;
        next();
      });
    }

   function zipRadius(zipcode, radius) {
        var url = '//www.zipcodeapi.com/rest/'+clientKey+'/radius.json/'+zipcode+'/'+radius+'/mile';

        if(useShirley){
            url = '//askshirley.org/zip/api/radius/'+clientKey+'/'+zipcode+'/'+radius;
        }

        $.ajax({
            url: url,
            dataType: 'text',
            complete: function(jqXHR, status){
                switch (status){
                    case 'success':
                        var data = JSON.parse(jqXHR.responseText);

                        if(data.error || data.error_msg){
                            messageHandler("There was a problem with your request. Please try again later", $mapform, 'error', false);
                            codeWrapper();
                        }
                        else {
                            findPlaces(data);
                        }
                        break;
                    case 'timeout':
                        messageHandler("The request took too long to complete. Please try again.", $mapform, 'error', false);
                        codeWrapper();
                        break;
                    case 'nocontent':
                        messageHandler("There was no content to process. Please try again.", $mapform, 'error', false);
                        codeWrapper();
                        break;
                    default:
                        messageHandler("There was an error. Please try again.", $mapform, 'error', false);
                        codeWrapper();
                        break;
                } 
            }
        });
    }

    function scriptInsertion(){
        var script = window.document.createElement('script');

        script.async = true;
        script.src = encodeURI(queryurl+'&tq='+selectClause + whereClauses[nextClause]+'&tqx=responseHandler:MapManager.queryPacker');
        script.onerror = function() {
            messageHandler("There was an error, please try again.", $mapform, 'error', false);
            codeWrapper();
        };
        var done = false;
        script.onload = script.onreadystatechange = function() {
            if (!done && (!this.readyState || this.readyState === 'loaded' || this.readyState === 'complete')) {
                done = true;
                script.onload = script.onreadystatechange = null;

                if (script.parentNode) {
                    return script.parentNode.removeChild(script);
                }
            }
        };
        window.document.getElementsByTagName('head')[0].appendChild(script);
    }

    function queryHandler() {
        switch ($mapform.data('map-type')) {
            case 'pharmacy':
                places = packedResults.map(function(row){
                    var place = {
                        nabp: row.c[0].v,
                        name: row.c[1].v,
                        address: row.c[2].v,
                        city: row.c[3].v,
                        state: row.c[4].v,
                        zip: row.c[5].v
                    };
                    return place;
                });
                var context = {
                    number: places.length
                };
                break;
            case 'seminar':
                var seminars = 0;
                
                packedResults.filter(function(row){
                    var semDate = new Date(row.c[4].v);
                    var now = new Date();

                    if(Date.parse(now.toDateString()) > Date.parse(semDate.toDateString())){
                        return false;
                    }
                    return true;
                }).map(function(row){
                    var semDate = new Date(row.c[4].v);
                    
                    var event = {
                        date: semDate.toDateString(),
                        time: semDate.toLocaleTimeString(navigator.language, {hour: '2-digit', minute:'2-digit'})
                    };

                    var index = getKeyByAddress(places, row.c[1].v);

                    if(index !== -1) {
                        places[index].events.push(event);
                        seminars+=1;
                    }
                    else {
                        var place = {
                            name: row.c[0].v,
                            address: row.c[1].v,
                            city: row.c[2].v,
                            state: row.c[5].v,
                            zip: row.c[3].v,
                            events:[]
                        };
                        place.events.push(event);
                        seminars+=1;
                        places.push(place);
                    }
                });

                var msgLoc = places.length+" Different Locations";

                if(places.length === 0){
                    msgLoc = "0 Locations";
                }
                else if (places.length === 1) {
                    msgLoc = "1 Location";
                }

                var context = {
                    locations: msgLoc,
                    number: seminars
                };
                break;
        }    

        var templateScript = $('#result-list').html();
        var template = Handlebars.compile(templateScript);

        $results.prepend(template(context));

        theNext();
    }

    function theNext() {

        if (nextAddress < places.length) {
            if (nextAddress < 10){
                delay = 100;
            }
            setTimeout('MapManager.codeAddress(MapManager.theNext)', delay);  
        } else {
            codeWrapper();
        }
    }

    function requestDirections(type, locationID, caller) { 
        var statusMessage = '';
        var element = 'none';

        if($('[name=starting-location]').length > 0) {
            origin = $('[name=starting-location]').val();
        } 

        var end = allMarkers[locationID].title;

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

        var request = {
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
        var route = directionResult.routes[0].legs[0];
        var templateScript = $('#direction-steps').html();
        var template = Handlebars.compile(templateScript);

        var context = {
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
      messageHandler(directionResult.routes[0].warnings, '#starting-loc', 'warning', false);
    }

    function findPlaces(zipcodes) {
        var whereClause, zipColumn;
        places = [];
        packedResults = [];
        whereClauses = [];
        nextClause = 0;
        queryurl = '';
        selectClause = '';

        switch ($mapform.data('map-type')) {
            case 'pharmacy':
                if($('[name=pharmacy]:checked').val() === 'pharm'){
                    queryurl = pharmurl;
                }
                else {
                    queryurl = pharmplusurl;
                }
                selectClause = 'SELECT A, B, C, D, E, F';
                zipColumn = 'F';
                break;
            case 'seminar':
                queryurl = semurl;
                selectClause = 'SELECT B, C, D, E, F, I';
                zipColumn = 'E';
                break;
        }

        //This is broken up into multiple queries because of freaking IE (character limit on jsonp scripts)
        zipcodes.zip_codes.map(function(row, index){
            if(index === 0 || ($.support.cors && index%41 === 0)) {
                whereClause = ' WHERE '+zipColumn+' = ' + row.zip_code;
            }
            else {
                whereClause += ' OR '+zipColumn+' = ' + row.zip_code;
            }

            if($.support.cors && index%40 === 0 && index !== 0){
                whereClauses.push(whereClause);
            } else if (index === zipcodes.zip_codes.length-1) {
                whereClauses.push(whereClause);
            }
        });

        scriptInsertion();
    }

    function codeWrapper() {
        $('#loading').toggle();

        if($('#loading').is(':visible')) {
            var i = 0;
            setInterval(function() {
                i = ++i % 4;
                $('#loading').html("Loading"+Array(i+1).join('.'));
            }, 500);
        }
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
        return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
    }

    function messageHandler(message, selector, messageType, isForm) {  
        if (isForm) {
            $("<div class='message message-label' role='alert'>"+message+"</div>").insertAfter(selector);
            $(selector).parent().parent().addClass(messageType);
            $(selector).attr('aria-invalid', 'true');
        }
        else {
            $("<div class='message message-label default-message-box "+messageType+"' role='alert'>"+message+"</div>").insertAfter(selector);
        }
    }
    
    function isMobile() {
        mobile = $mapwrapper.css('display') === 'none' ? true : false;
    }
    
    //Can put this in a namespace to keep it from polluting the global space
    function queryPacker(res) {
        res.table.rows.map(function(row){
            packedResults.push(row);
        });
        nextClause+=1;
        if(nextClause < whereClauses.length) {
            scriptInsertion(encodeURI(queryurl+'&tq='+selectClause + whereClauses[nextClause]+'&tqx=responseHandler:MapManager.queryPacker'));
        } else {       
            queryHandler();
        }
    }
    
    function handleMapForm(evt) {
        codeWrapper();

        var radius = $('[name=radius]:checked').val();
        var location = $('[name=location]').val();
        origin = location;
        nextAddress = 0;
        delay = 0;

        allMarkers.map(function(row){
            row.setMap(null);
        });
        
        allMarkers = []; 

        $directions.html("");
        $locations.html("");
        $('#finished h2, .message, #number-results').remove();
        $('.error, .warning').removeClass('error warning');
        $('[aria-invalid=true]').attr('aria-invalid', 'false');


        if($mapform.data('map-type') === 'pharmacy' && !$('[name=pharmacy]:checked').val()) {
            messageHandler("Please select a pharmacy type.", $mapform, 'error', false);
            codeWrapper();
            return;
        }

        if (!radius){
            messageHandler("Please select a radius.", $mapform, 'error', false);
            codeWrapper();
            return;
        }

        if(isInt(location)) {
            if(/(^\d{5}$)/.test(location)) {
                zipRadius(location, radius);
            } 
            else {
                messageHandler("That is not a valid zipcode", '[name=location]', 'error', true);
                codeWrapper();
            }
        } 
        else {
            if(location === ''){
                messageHandler("That is not a valid ZIP or City, State combo", '[name=location]', 'error', true);
                codeWrapper();
                return;
            }

            location = location.split(',');

            if(location.length !== 2){
                messageHandler("That is not a valid City, State combo", '[name=location]', 'error', true);
                codeWrapper();
                return;
            }

            location = location.map(function(row){
                return row.trim();
            });

            var found = false;

            $.each(states, function(i, v) {
                if (v.name.search(new RegExp(location[1], "i")) !== -1 || v.abbreviation.search(new RegExp(location[1], "i")) !== -1) {
                    found = true;
                    location[1] = v.abbreviation;

                    var url = '//www.zipcodeapi.com/rest/'+clientKey+'/city-zips.json/'+location[0]+'/'+location[1];

                    if(useShirley){
                        url = '//askshirley.org/zip/api/cityzips/'+clientKey+'/'+location[0]+'/'+location[1];
                    }

                    $.ajax({
                        url: url,
                        dataType: 'text',
                        complete: function(jqXHR, status){
                            switch (status){
                                case 'success':
                                    var data = JSON.parse(jqXHR.responseText);

                                    if(data.error || data.error_msg){
                                        messageHandler("There was a problem with your request. Please try again later", $mapform, 'error', false);
                                        codeWrapper();
                                    }
                                    else if(data.zip_codes.length > 0) {
                                        zipRadius(data.zip_codes[0], radius);
                                    }
                                    else {
                                        messageHandler("There was no data for your location. Please try a different city/state combination.", $mapform, 'error', false);
                                        codeWrapper();
                                    }
                                    break;
                                case 'timeout':
                                    messageHandler("The request took to long to complete. Please try again.", $mapform, 'error', false);
                                    codeWrapper();
                                    break;
                                case 'nocontent':
                                    messageHandler("There was no content to process. Please try again.", $mapform, 'error', false);
                                    codeWrapper();
                                    break;
                                default:
                                    messageHandler("There was an error. Please try again.", $mapform, 'error', false);
                                    codeWrapper();
                                    break;
                            } 
                        }
                    });
                }
             });

            if(!found){
                if(location[1].length === 2) {
                    messageHandler("Incorrect State Abbreviation", '[name=location]', 'error', true);
                }
                else {
                    messageHandler("Incorrect State Name", '[name=location]', 'error', true);
                }
            }
        }   
    }

    function handleResultsLocation(evt) {
        $('.default-message-box').remove();
        $('.message').remove();
        var type = $('.direction-links .button.active').attr('class').replace(' active', '').replace(' button', '');
        requestDirections(type, $('.direction-links .button.active').parent().attr('data-marker-index'), 'starting-loc');
    }
    
    function handleDirections(evt) {
        if(!mobile){
            $('.default-message-box').remove();
            $('.direction-links .button.active').removeClass('active');
            var type = $(evt.currentTarget).attr('class').replace(' active', '').replace(' button', '');
            $(evt.currentTarget).addClass('active');
            requestDirections(type, $(evt.currentTarget).parent().attr('data-marker-index'), 'directions-button');
        }
    }
    
    function loadStates(data) {
        states = data;
    }
    
    function init(opts) {
        
        $mapform = $(opts.mapform);
        $results = $(opts.results);
        $locations = $(opts.locations);
        $mapwrapper = $(opts.mapwrapper);
        $directions = $(opts.directions);
        
        loaded = true;
        isMobile();

        if('XDomainRequest' in window && window.XDomainRequest !== null) {
            useShirley = true;
            $.support.cors = true;
            clientKey = 'pD5ltovTQHNvhP32e2zQ';
        }

        if(getCookie('cms_user_zip_code') !== null){
            $('[name=location]').val(getCookie('cms_user_zip_code'));
        }

        //listen for map form submit
        $mapform.bind('submit',handleMapForm.bind(this));

        //listen for clicks on starting location button
        $results.on('click touch','#starting-loc button',handleResultsLocation.bind(this));
        
        //listen for clicks on direction buttons
        $locations.on('click touch','.direction-links .button',handleDirections.bind(this));  
    }
    
    var
        states,
        origin,
        allMarkers = [],
        nextAddress = 0,
        nextClause = 0,
        whereClauses = [],
        queryurl,
        selectClause,
        delay = 0,
        places = [],
        packedResults = [],
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
        useShirley = false,
        clientKey = 'js-k0p09YraX0SYvCu2lCxAcVLcBxZMbEhwEGwNEYXCYQAut4xOH4oTZa6AH6nTKEqp',
        pharmurl = '//spreadsheets.google.com/a/google.com/tq?key=1X2y2MVq82sgCXznHMdJEbyqIheL-SJ1dq2xxMO7kUkY',
        pharmplusurl = '//spreadsheets.google.com/a/google.com/tq?key=14du8qyaID-DmqTHMEfIpy_W6l2dmOluzq8qfVfIdsbg',
        semurl = '//spreadsheets.google.com/a/google.com/tq?key=15R_yJWOph16dwxWtzfnWCCt6z8DjFiId3MVu-KLuF7g',
        mobile = false,
        loaded = false,
        
        //Dom References
        $mapform,
        $results,
        $locations,
        $mapwrapper,
        $directions,
        
        publicAPI = {
            //I really don't want to expose these 3 methods to the public space, 
            //but I can't think of a way to get around max call stack errors or 
            //JSONP calls
            codeAddress: codeAddress,
            theNext: theNext,
            queryPacker: queryPacker,
            isMobile: isMobile,
            loaded: loaded,
            loadStates: loadStates,
            init: init
        }
    ;
    
    return publicAPI;
})();

MapManager.loadStates([{name:'Alabama',abbreviation:'AL'},{name:'Alaska',abbreviation:'AK'},{name:'American Samoa',abbreviation:'AS'},{name:'Arizona',abbreviation:'AZ'},{name:'Arkansas',abbreviation:'AR'},{name:'California',abbreviation:'CA'},{name:'Colorado',abbreviation:'CO'},{name:'Connecticut',abbreviation:'CT'},{name:'Delaware',abbreviation:'DE'},{name:'District Of Columbia',abbreviation:'DC'},{name:'Federated States Of Micronesia',abbreviation:'FM'},{name:'Florida',abbreviation:'FL'},{name:'Georgia',abbreviation:'GA'},{name:'Guam',abbreviation:'GU'},{name:'Hawaii',abbreviation:'HI'},{name:'Idaho',abbreviation:'ID'},{name:'Illinois',abbreviation:'IL'},{name:'Indiana',abbreviation:'IN'},{name:'Iowa',abbreviation:'IA'},{name:'Kansas',abbreviation:'KS'},{name:'Kentucky',abbreviation:'KY'},{name:'Louisiana',abbreviation:'LA'},{name:'Maine',abbreviation:'ME'},{name:'Marshall Islands',abbreviation:'MH'},{name:'Maryland',abbreviation:'MD'},{name:'Massachusetts',abbreviation:'MA'},{name:'Michigan',abbreviation:'MI'},{name:'Minnesota',abbreviation:'MN'},{name:'Mississippi',abbreviation:'MS'},{name:'Missouri',abbreviation:'MO'},{name:'Montana',abbreviation:'MT'},{name:'Nebraska',abbreviation:'NE'},{name:'Nevada',abbreviation:'NV'},{name:'New Hampshire',abbreviation:'NH'},{name:'New Jersey',abbreviation:'NJ'},{name:'New Mexico',abbreviation:'NM'},{name:'New York',abbreviation:'NY'},{name:'North Carolina',abbreviation:'NC'},{name:'North Dakota',abbreviation:'ND'},{name:'Northern Mariana Islands',abbreviation:'MP'},{name:'Ohio',abbreviation:'OH'},{name:'Oklahoma',abbreviation:'OK'},{name:'Oregon',abbreviation:'OR'},{name:'Palau',abbreviation:'PW'},{name:'Pennsylvania',abbreviation:'PA'},{name:'Puerto Rico',abbreviation:'PR'},{name:'Rhode Island',abbreviation:'RI'},{name:'South Carolina',abbreviation:'SC'},{name:'South Dakota',abbreviation:'SD'},{name:'Tennessee',abbreviation:'TN'},{name:'Texas',abbreviation:'TX'},{name:'Utah',abbreviation:'UT'},{name:'Vermont',abbreviation:'VT'},{name:'Virgin Islands',abbreviation:'VI'},{name:'Virginia',abbreviation:'VA'},{name:'Washington',abbreviation:'WA'},{name:'West Virginia',abbreviation:'WV'},{name:'Wisconsin',abbreviation:'WI'},{name:'Wyoming',abbreviation:'WY'}]);

//This module is not packaged with the other code in this file.
var GeoLocation = (function() {
    function googleCoding(lat, lng){
        var isSuccessful = true;
        var pos = new google.maps.LatLng(lat, lng);
        geocoder = new google.maps.Geocoder();
        geocoder.geocode({'latLng': pos}, function(results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                results[0].address_components.map(function(row){
                    if(row.types[0] === 'postal_code'){
                        completionCallback(row.short_name);
                    }
                });
            }
            else {
                isSuccessful = false;
            }
        });
        return isSuccessful;
    }

    function shirleyCoding(lat, lng){
        $.ajax({
            url: '//askshirley.org/zip/api/geocode/pD5ltovTQHNvhP32e2zQ/'+lat+'/'+lng,
            dataType: 'text',
            complete: function(jqXHR, status){
                switch (status){
                    case 'success':
                        var data = JSON.parse(jqXHR.responseText);
                        if(data.error || data.error_msg){
                            console.log("There was a problem with your request. Please try again later");
                        }
                        else {
                            zipcode = data.zip_codes[0].zip_code;
                        }
                        break;
                    default:
                        console.log("There was an error. Please try again.");
                        break;
                } 
            }
        });
    }
    
    function handleNoGeolocation(errorFlag) {
        if (errorFlag) {
            console.log("Error: The Geolocation service failed.");
        } else {
            console.log("Error: Your browser doesn\'t support geolocation.");
        }

    }
    
    function init(opts){
        //$error = opts.error
        
        if(getCookie('cms_user_zip_code') === null){
            completionCallback = opts.completionCallback;
            if(navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    if(!googleCoding(position.coords.latitude, position.coords.longitude))
                        if(!shirleyCoding(position.coords.latitude, position.coords.longitude)){
                            console.log('Error: We sux.');
                        }
                }, function() {
                    //Geolocation bit the big one and failed
                    handleNoGeolocation(true);
                });
            } else {
                // Browser doesn't support Geolocation
                handleNoGeolocation(false);
            }
        }
    }
    
    var
        zipcode,
        geocoder,
        completionCallback,
        
        //Where should any errors be displayed? 
        //Add a default popup if no option is entered?
        $error,

        publicAPI = {
            init: init
        }
    ;

    return publicAPI;
})();

$(window).on('load resize',function(e){
    if(e.type === 'load') {
        GeoLocation.init({completionCallback:function(){
                            $('[name=location]').val(arguments[0]);
                        }});
        MapManager.init({
            mapform: '#mapform',
            results : '#results',
            locations : '#locations',
            mapwrapper : '#map-wrapper',
            directions: '#directions',
            locbutton: '#current-loc'
        });
    } else if (e.type === 'resize' && MapManager.loaded) {
        MapManager.isMobile();
    }
});