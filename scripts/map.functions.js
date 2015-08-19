/*
 * TODO: LINK UP TO SOCIAL MEDIA/GOOGLE PLACES TO ADD HOURS/PICTURES OF BUSINESS
 * TODO: IE8 DESIGN TWEAKS?
 */
function MapManager(){
    this.states = [];
}

MapManager.prototype.codeAddress = function(next){
    var location = this.toTitleCase(this.places[this.nextAddress]['name']) + ' ' + this.toTitleCase(this.places[this.nextAddress]['address']) + ' ' + 
            this.toTitleCase(this.places[this.nextAddress]['city']) + ' ' + this.places[this.nextAddress]['state'] + ' ' 
            + this.places[this.nextAddress]['zip'];
    var templateScript;

    var number = this.nextAddress + 1;
    //Goes up the scope chain?
    var self = this;
    this.geocoder.geocode( { 'address': location }, function(results, status) {

    if (status == google.maps.GeocoderStatus.OK) {

        if(!self.mobile){
           self.map.setCenter(results[0].geometry.location);

            var iconImage = {
                url: '//askshirley.org/images/green/marker'+number+'.png',
                size: new google.maps.Size(32, 32),
                origin: new google.maps.Point(0,0),
                anchor: new google.maps.Point(10, 33)
            };

            var marker = new google.maps.Marker({
                map: self.map,
                position: results[0].geometry.location,
                icon:iconImage,
                title:location,
                optimized: false,
                zIndex: (self.nextAddress + 1)
            });

            self.allMarkers.push(marker);

            var bounds = new google.maps.LatLngBounds();
            for(i=0;i< self.allMarkers.length;i++) {
                bounds.extend(self.allMarkers[i].getPosition());
            }
            self.map.setCenter(bounds.getCenter());
            self.map.fitBounds(bounds);
            templateScript = $("#location-list").html();
        }
        else {
            templateScript = $("#location-list-mobile").html();
        }

        var template = Handlebars.compile(templateScript);

        switch ($("#mapform").data("map-type")) {
            case "pharmacy":
                var context = {
                    index: self.nextAddress,
                    number: number,
                    name: self.toTitleCase(self.places[self.nextAddress]['name']),
                    address: self.toTitleCase(self.places[self.nextAddress]['address']),
                    city: self.toTitleCase(self.places[self.nextAddress]['city']),
                    state: self.places[self.nextAddress]['state'],
                    zip: self.places[self.nextAddress]['zip'],
                    nabp: self.places[self.nextAddress]['nabp']
                };
                break;
            case "seminar":  
                var context = {
                    index: self.nextAddress,
                    number: number,
                    name: self.toTitleCase(self.places[self.nextAddress]['name']),
                    address: self.toTitleCase(self.places[self.nextAddress]['address']),
                    city: self.toTitleCase(self.places[self.nextAddress]['city']),
                    state: self.places[self.nextAddress]['state'],
                    zip: self.places[self.nextAddress]['zip'],
                    events: []
                };

                context['events'] = self.places[self.nextAddress]['events'].map(function(row,index){
                    var event = {
                        number: index+1,
                        date: row['date'],
                        time: row['time']
                    };
                    return event;
                });
                break;
        }

        if(self.mobile){
            var root;
            if($("#mapform").data("map-type") === "pharmacy") {
                root = "maps.google.com?saddr="+origin+"&daddr="+context['name']+" "+context['address']+" "+context['city']+" "+context['state'];
            }
            else {
                root = "maps.google.com?saddr="+origin+"&daddr="+context['address']+" "+context['city']+" "+context['state'];
            }

            context['walk'] = encodeURI(root+"&directionsmode=walk");
            context['bike'] = encodeURI(root+"&directionsmode=bike");
            context['transit'] = encodeURI(root+"&directionsmode=transit");
            context['drive'] = encodeURI(root+"&directionsmode=drive");
        }

        $("#locations").append(template(context));

    } else {
        if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
            self.nextAddress--;
            self.delay++;
          } else {
            self.messageHandler("There was an external error, please try again", "#number-results", "error", false);
        } 
    }
    self.nextAddress++;
    next(self);
  });
};

MapManager.prototype.zipRadius = function(zipcode, radius) {
    var url = "//www.zipcodeapi.com/rest/"+this.clientKey+"/radius.json/"+zipcode+"/"+radius+"/mile";
    var self = this;

    if(this.useShirley){
        url = "//askshirley.org/zip/api/radius/"+this.clientKey+"/"+zipcode+"/"+radius;
    }

    $.ajax({
        url: url,
        dataType: 'text',
        complete: function(jqXHR, status){
            switch (status){
                case 'success':
                    var data = JSON.parse(jqXHR.responseText);

                    if(data.error || data.error_msg){
                        self.messageHandler("There was a problem with your request. Please try again later", "#mapform", "error", false);
                        self.codeWrapper();
                    }
                    else {
                        self.findPlaces(data);
                    }
                    break;
                case 'timeout':
                    self.messageHandler("The request took too long to complete. Please try again.", "#mapform", "error", false);
                    self.codeWrapper();
                    break;
                case 'nocontent':
                    self.messageHandler("There was no content to process. Please try again.", "#mapform", "error", false);
                    self.codeWrapper();
                    break;
                default:
                    self.messageHandler("There was an error. Please try again.", "#mapform", "error", false);
                    self.codeWrapper();
                    break;
            } 
        }
    });
};

MapManager.prototype.scriptInsertion = function(){
    var script = window.document.createElement('script');

    script.async = true;
    script.src = encodeURI(this.queryurl+'&tq='+this.selectClause + this.whereClauses[this.nextClause]+'&tqx=responseHandler:queryPacker');
    script.onerror = function() {
        this.messageHandler("There was an error, please try again.", "#mapform", "error", false);
        this.codeWrapper();
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
};

MapManager.prototype.queryHandler = function() {
    switch ($("#mapform").data("map-type")) {
        case "pharmacy":
            this.places = this.packedResults.map(function(row){
                var place = {
                    nabp: row['c'][0].v,
                    name: row['c'][1].v,
                    address: row['c'][2].v,
                    city: row['c'][3].v,
                    state: row['c'][4].v,
                    zip: row['c'][5].v
                };
                return place;
            });
            var context = {
                number: this.places.length
            };
            break;
        case "seminar":
            var seminars = 0;

            for(i = 0; i < this.packedResults.length; i++) {
                var row = this.packedResults[i];

                var semDate = new Date(row['c'][4].v);
                var now = new Date();

                if(Date.parse(now.toDateString()) > Date.parse(semDate.toDateString())){
                    continue;
                }

                var event = {
                    date: semDate.toDateString(),
                    time: semDate.toLocaleTimeString(navigator.language, {hour: '2-digit', minute:'2-digit'})
                };

                var index = getKeyByAddress(this.places, row['c'][1].v);

                if(index !== -1) {
                    this.places[index]['events'].push(event);
                    seminars++;
                }
                else {
                    var place = {
                        name: row['c'][0].v,
                        address: row['c'][1].v,
                        city: row['c'][2].v,
                        state: row['c'][5].v,
                        zip: row['c'][3].v,
                        events:[]
                    };
                    place['events'].push(event);
                    seminars++;
                    this.places.push(place);
                }
            }

            var msgLoc = this.places.length+" Different Locations";

            if(this.places.length == 0){
                msgLoc = "0 Locations";
            }
            else if (this.places.length == 1) {
                msgLoc = "1 Location";
            }

            var context = {
                locations: msgLoc,
                number: seminars
            };
            break;
    }    

    var templateScript = $("#result-list").html();
    var template = Handlebars.compile(templateScript);

    $("#results").prepend(template(context));

    this.theNext();
};

MapManager.prototype.theNext = function() {
    var self = this;
    if(arguments.length == 1) {
        self = arguments[0];
    }
    
    if (self.nextAddress < self.places.length) {
        if (self.nextAddress < 10){
            self.delay = 100;
        }
        setTimeout(self.codeAddress(self.theNext), self.delay);  
    } else {
        self.codeWrapper();
    }
};

MapManager.prototype.requestDirections = function(type, locationID, caller) { 
    var statusMessage = '';
    var element = 'none';

    if($("[name=starting-location]").length > 0) {
        this.origin = $("[name=starting-location]").val();
    } 

    var end = this.allMarkers[locationID]['title'];

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
    this.directionsService.route(request, function(result, status) {

        if (status == google.maps.DirectionsStatus.OK) {
            $("#directions").html('');
            this.directionsDisplay.setDirections(result);
            this.showSteps(result);
        }
        else {
            switch (status) {
                case google.maps.DirectionsStatus.NOT_FOUND:
                    statusMessage = 'We could not find that location. Make sure your search is spelled correctly.';
                    break;
                case google.maps.DirectionsStatus.ZERO_RESULTS:
                    statusMessage = 'There are no available routes between the origin and destination. Please try another transit mode.';
                    break;
                case google.maps.DirectionsStatus.INVALID_REQUEST:
                    statusMessage = 'There was an issue with the request. Please try another request.';
                    break; 
                case google.maps.DirectionsStatus.OVER_QUERY_LIMIT:
                    statusMessage = 'There have been too many recent requests. Please wait a few minutes and try again';
                    break; 
                default:
                    statusMessage = 'There was an error retrieving directions. Please try again.';
                    break;
            }
            var isForm = false;
            if(caller === 'starting-loc') {
                element = '[name=starting-location]';
                isForm = true;
            }
            else {
                element = '#filter'
            }
            this.messageHandler(statusMessage, element, 'error', isForm);
        }
    });
};

MapManager.prototype.showSteps = function(directionResult) {
    var route = directionResult.routes[0].legs[0];
    var templateScript = $("#direction-steps").html();
    var template = Handlebars.compile(templateScript);

    var context = {
        origin: this.origin,
        distance: route.distance.text,
        duration: route.duration.text,
        steps: []
    };

  for (var i = 0; i < route.steps.length; i++) {
    var step = {
        instructions: route.steps[i].instructions,
        distance: route.steps[i].distance.text,
        duration: route.steps[i].duration.text
    };
    context['steps'].push(step);
  }

  $("#directions").append(template(context));
  this.messageHandler(directionResult.routes[0].warnings, '#starting-loc', 'warning', false);
};

MapManager.prototype.findPlaces = function(zipcodes) {
    var whereClause, zipColumn;
    this.places = [];
    this.packedResults = [];
    this.whereClauses = [];
    this.nextClause = 0;
    this.queryurl = '';
    this.selectClause = '';

    switch ($("#mapform").data("map-type")) {
        case "pharmacy":
            if($("[name=pharmacy]:checked").val() === "pharm"){
                this.queryurl = this.pharmurl;
            }
            else {
                this.queryurl = this.pharmplusurl;
            }
            this.selectClause = "SELECT A, B, C, D, E, F";
            zipColumn = "F";
            break;
        case "seminar":
            this.queryurl = this.semurl;
            this.selectClause = "SELECT B, C, D, E, F, I";
            zipColumn = "E";
            break;
    }

    //This is broken up into multiple queries because of freaking IE (character limit on jsonp scripts)
    for(var i = 0; i < zipcodes.zip_codes.length; i++){
        if(i == 0 || ($.support.cors && i%41 === 0)) {
            whereClause = " WHERE "+zipColumn+" = " + zipcodes.zip_codes[i].zip_code;
        }
        else {
            whereClause += " OR "+zipColumn+" = " + zipcodes.zip_codes[i].zip_code;
        }

        if($.support.cors && i%40 === 0 && i != 0){
            this.whereClauses.push(whereClause);
        } else if (i === zipcodes.zip_codes.length-1) {
            this.whereClauses.push(whereClause);
        }
    } 

    this.scriptInsertion();
};

MapManager.prototype.codeWrapper = function() {
    $('#loading').toggle();

    if($('#loading').is(':visible')) {
        var i = 0;
        setInterval(function() {
            i = ++i % 4;
            $("#loading").html("Loading"+Array(i+1).join("."));
        }, 500);
    }
}

MapManager.prototype.isInt = function(value) {
  if (isNaN(value)) {
    return false;
  }
  var x = parseFloat(value);
  return (x | 0) === x;
};

MapManager.prototype.getKeyByAddress = function(obj, str) {
    var returnKey = -1;

    $.each(obj, function(key, info) {
        if (info.address == str) {
           returnKey = key;
           return false; 
        };   
    });
    return returnKey;       
};

MapManager.prototype.loadStates = function(data) {
    this.states.push(data);
};

MapManager.prototype.toTitleCase = function(str) {
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
};

MapManager.prototype.messageHandler = function(message, selector, messageType, isForm) {  
    if (isForm) {
        $("<div class='message message-label' role='alert'>"+message+"</div>").insertAfter(selector);
        $(selector).parent().parent().addClass(messageType);
        $(selector).attr('aria-invalid', 'true');
    }
    else {
        $("<div class='message message-label default-message-box "+messageType+"' role='alert'>"+message+"</div>").insertAfter(selector);
    }
};

MapManager.prototype.handleMapForm = function(evt) {
    this.codeWrapper();

    var radius = $("[name=radius]:checked").val();
    var location = $("[name=location]").val()
    this.origin = location;
    this.allMarkers = [];  
    this.nextAddress = 0;
    this.delay = 0;
    
    for (var i = 0; i < this.allMarkers.length; i++) {
        this.allMarkers[i].setMap(null);
    }

    $("#locations, #directions").html("");
    $("#finished h2, .message, #number-results").remove();
    $(".error, .warning").removeClass("error warning");
    $("[aria-invalid=true]").attr("aria-invalid", "false");
    

    if(this.$mapform.data("map-type") === 'pharmacy' && !$("[name=pharmacy]:checked").val()) {
        this.messageHandler("Please select a pharmacy type.", "#mapform", "error", false);
        this.codeWrapper();
        return;
    }

    if (!radius){
        this.messageHandler("Please select a radius.", "#mapform", "error", false);
        this.codeWrapper();
        return;
    }

    if(this.isInt(location)) {
        if(/(^\d{5}$)/.test(location)) {
            this.zipRadius(location, radius);
        } 
        else {
            this.messageHandler("That is not a valid zipcode", "[name=location]", "error", true);
            this.codeWrapper();
        }
    } 
    else {
        if(location == ''){
            this.messageHandler("That is not a valid ZIP or City, State combo", "[name=location]", "error", true);
            this.codeWrapper();
            return;
        }

        location = location.split(',');

        if(location.length !== 2){
            this.messageHandler("That is not a valid City, State combo", "[name=location]", "error", true);
            this.codeWrapper();
            return;
        }

        for(var i = 0; i < location.length; i++){
            location[i] = location[i].trim();
        }  

        var found = false;
        var self = this;

        $.each(this.states, function(i, v) {
            if (v.name.search(new RegExp(location[1], "i")) != -1 || v.abbreviation.search(new RegExp(location[1], "i")) != -1) {
                found = true;
                location[1] = v.abbreviation;

                var url = "//www.zipcodeapi.com/rest/"+this.clientKey+"/city-zips.json/"+location[0]+"/"+location[1];

                if(useShirley){
                    url = "//askshirley.org/zip/api/cityzips/"+this.clientKey+"/"+location[0]+"/"+location[1];
                }

                $.ajax({
                    "url": url,
                    "dataType": 'text',
                    complete: function(jqXHR, status){
                        switch (status){
                            case 'success':
                                var data = JSON.parse(jqXHR.responseText);

                                if(data.error || data.error_msg){
                                    self.messageHandler("There was a problem with your request. Please try again later", "#mapform", "error", false);
                                    self.codeWrapper();
                                }
                                else if(data.zip_codes.length > 0) {
                                    self.zipRadius(data.zip_codes[0], radius);
                                }
                                else {
                                    self.messageHandler("There was no data for your location. Please try a different city/state combination.", "#mapform", "error", false);
                                    self.codeWrapper();
                                };
                                break;
                            case 'timeout':
                                self.messageHandler("The request took to long to complete. Please try again.", "#mapform", "error", false);
                                self.codeWrapper();
                                break;
                            case 'nocontent':
                                self.messageHandler("There was no content to process. Please try again.", "#mapform", "error", false);
                                self.codeWrapper();
                                break;
                            default:
                                self.messageHandler("There was an error. Please try again.", "#mapform", "error", false);
                                self.codeWrapper();
                                break;
                        } 
                    }
                });
            }
         });

        if(!found){
            if(location[1].length == 2) {
                this.messageHandler("Incorrect State Abbreviation", "[name=location]", "error", true);
            }
            else {
                this.messageHandler("Incorrect State Name", "[name=location]", "error", true);
            }
        }
    }   
};

MapManager.prototype.handleResultsLocation = function(evt) {
    $('.default-message-box').remove();
    $('.message').remove();
    var type = $('.direction-links .button.active').attr("class").replace(' active', '').replace(' button', '');;
    requestDirections(type, $('.direction-links .button.active').parent().attr("data-marker-index"), 'starting-loc');
};

MapManager.prototype.init = function(opts) {
    console.log('init');
    this.geocoder = new google.maps.Geocoder();
    this.map = new google.maps.Map(document.getElementById('map-canvas'), {
            center: new google.maps.LatLng(34.9983818,-99.99967040000001),
            zoom: 4,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

    this.directionsDisplay = new google.maps.DirectionsRenderer({
        map: this.map
    });
    this.directionsService = new google.maps.DirectionsService();;
    this.useShirley = false;
    this.clientKey = "js-k0p09YraX0SYvCu2lCxAcVLcBxZMbEhwEGwNEYXCYQAut4xOH4oTZa6AH6nTKEqp";
    this.pharmurl = "//spreadsheets.google.com/a/google.com/tq?key=1X2y2MVq82sgCXznHMdJEbyqIheL-SJ1dq2xxMO7kUkY";
    this.pharmplusurl = "//spreadsheets.google.com/a/google.com/tq?key=14du8qyaID-DmqTHMEfIpy_W6l2dmOluzq8qfVfIdsbg";
    this.semurl = "//spreadsheets.google.com/a/google.com/tq?key=15R_yJWOph16dwxWtzfnWCCt6z8DjFiId3MVu-KLuF7g";
    this.mobile = false;
    
    this.service;
    this.origin;
    this.allMarkers = [];
    this.nextAddress = 0;
    this.nextClause = 0;
    this.whereClauses = [];
    this.queryurl;
    this.selectClause;
    this.delay = 0;
    this.places = [];
    this.packedResults = [];
    
    this.$mapform = $(opts.mapform);
    this.$results = $(opts.results);
    
    //TODO:Clean
    if($('#map-wrapper').css('display') === 'none'){
        this.mobile = true;
    }
    else {
       this.mobile = false;
    }

    if('XDomainRequest' in window && window.XDomainRequest !== null) {
        this.useShirley = true;
        $.support.cors = true;
        this.clientKey = "pD5ltovTQHNvhP32e2zQ";
    }

    if(getCookie('cms_user_zip_code') !== null){
        $("[name=location]").val(getCookie('cms_user_zip_code'));
    }
    
    //listen for map form submit
    this.$mapform.bind("submit",this.handleMapForm.bind(this));
    
    //listen for clicks on starting location button
    this.$results.on("click touch","#starting-loc button",this.handleResultsLocation.bind(this));

    //add handler
    if(!this.mobile){
        $('#locations').on('click touch', '.direction-links .button', function(){
            $('.default-message-box').remove();
            $('.direction-links .button.active').removeClass('active');
            var type = $(this).attr("class").replace(' active', '').replace(' button', '');;
            $(this).addClass('active');
            requestDirections(type, $(this).parent().attr("data-marker-index"), 'directions-button');
        });
    }
};

var theMap = new MapManager();
var loaded = false;

theMap.loadStates([{"name":"Alabama","abbreviation":"AL"},{"name":"Alaska","abbreviation":"AK"},{"name":"American Samoa","abbreviation":"AS"},{"name":"Arizona","abbreviation":"AZ"},{"name":"Arkansas","abbreviation":"AR"},{"name":"California","abbreviation":"CA"},{"name":"Colorado","abbreviation":"CO"},{"name":"Connecticut","abbreviation":"CT"},{"name":"Delaware","abbreviation":"DE"},{"name":"District Of Columbia","abbreviation":"DC"},{"name":"Federated States Of Micronesia","abbreviation":"FM"},{"name":"Florida","abbreviation":"FL"},{"name":"Georgia","abbreviation":"GA"},{"name":"Guam","abbreviation":"GU"},{"name":"Hawaii","abbreviation":"HI"},{"name":"Idaho","abbreviation":"ID"},{"name":"Illinois","abbreviation":"IL"},{"name":"Indiana","abbreviation":"IN"},{"name":"Iowa","abbreviation":"IA"},{"name":"Kansas","abbreviation":"KS"},{"name":"Kentucky","abbreviation":"KY"},{"name":"Louisiana","abbreviation":"LA"},{"name":"Maine","abbreviation":"ME"},{"name":"Marshall Islands","abbreviation":"MH"},{"name":"Maryland","abbreviation":"MD"},{"name":"Massachusetts","abbreviation":"MA"},{"name":"Michigan","abbreviation":"MI"},{"name":"Minnesota","abbreviation":"MN"},{"name":"Mississippi","abbreviation":"MS"},{"name":"Missouri","abbreviation":"MO"},{"name":"Montana","abbreviation":"MT"},{"name":"Nebraska","abbreviation":"NE"},{"name":"Nevada","abbreviation":"NV"},{"name":"New Hampshire","abbreviation":"NH"},{"name":"New Jersey","abbreviation":"NJ"},{"name":"New Mexico","abbreviation":"NM"},{"name":"New York","abbreviation":"NY"},{"name":"North Carolina","abbreviation":"NC"},{"name":"North Dakota","abbreviation":"ND"},{"name":"Northern Mariana Islands","abbreviation":"MP"},{"name":"Ohio","abbreviation":"OH"},{"name":"Oklahoma","abbreviation":"OK"},{"name":"Oregon","abbreviation":"OR"},{"name":"Palau","abbreviation":"PW"},{"name":"Pennsylvania","abbreviation":"PA"},{"name":"Puerto Rico","abbreviation":"PR"},{"name":"Rhode Island","abbreviation":"RI"},{"name":"South Carolina","abbreviation":"SC"},{"name":"South Dakota","abbreviation":"SD"},{"name":"Tennessee","abbreviation":"TN"},{"name":"Texas","abbreviation":"TX"},{"name":"Utah","abbreviation":"UT"},{"name":"Vermont","abbreviation":"VT"},{"name":"Virgin Islands","abbreviation":"VI"},{"name":"Virginia","abbreviation":"VA"},{"name":"Washington","abbreviation":"WA"},{"name":"West Virginia","abbreviation":"WV"},{"name":"Wisconsin","abbreviation":"WI"},{"name":"Wyoming","abbreviation":"WY"}]);

//Can put this in a namespace to keep it from polluting the global space
function queryPacker(res) {
    for(var i = 0; i < res.table.rows.length; i++){
        theMap.packedResults.push(res.table.rows[i]);
    }
    theMap.nextClause++;
    if(theMap.nextClause < theMap.whereClauses.length) {
        theMap.scriptInsertion(encodeURI(theMap.queryurl+'&tq='+theMap.selectClause + theMap.whereClauses[theMap.nextClause]+'&tqx=responseHandler:queryPacker'));
    } else {       
        theMap.queryHandler();
    }
}

$(window).on('load resize',function(e){
    if(!loaded){
        theMap.init({
            mapform: "#mapform",
            results : "#results"
        });
        loaded = true;
    }
    
});  

//document.ready?
//Handler for directions button
//Handler for both submit button
//Handler for query packer on the jquery element
//Seperate object for seminars and pharmacy? need an object prototype
