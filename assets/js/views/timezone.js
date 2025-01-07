var timezoneObject = window.timezoneObject;
var region = window.region;
var city = window.city
window.onload = function() {
    var regionSel = document.getElementById("region");
    var citySel = document.getElementById("city");
    document.getElementById('region').options.length = 0;
    document.getElementById('city').options.length = 0;
    for (var x in timezoneObject) {
        var object = new Option(x, x);
        regionSel.options[regionSel.options.length] = object;
        if (x == region) {
            object.setAttribute("selected", "selected");
            for (var y in timezoneObject[x]) {
                var object1 = new Option(y, y);
                citySel.options[citySel.options.length] = object1;
                if(y == city) {
                    object1.setAttribute("selected", "selected");
                }
            }
        }
    }
    regionSel.onchange = function() {
        //empty cities
        citySel.length = 0;
        //display correct values
        for (var y in timezoneObject[this.value]) {
            citySel.options[citySel.options.length] = new Option(y, y);
        }
    }
}
