<?php
    // Searchbar functionality
    if ($_GET['q'] != null) {
        $data = file_get_contents("https://emo.lv/weather-api/forecast/?city=" . htmlspecialchars($_GET['q']));
    } else {
        $data = file_get_contents("https://emo.lv/weather-api/forecast/?city=cesis,latvia");
    }

    $weatherData = json_decode($data, true);

    // Obtains the IANA timeztone format, by using longitude and latitude positioning from another API
    $tzData = file_get_contents(htmlspecialchars_decode("https://api.geotimezone.com/public/timezone?latitude=" . $weatherData["city"]["coord"]["lat"] . "&longitude=" . $weatherData["city"]["coord"]["lon"]));
    $timezone = json_decode($tzData, true);

    // Template for the schedule system
    $template = '
                <div class="flex items-center space-between item">
                    <div class="item-left flex items-center">
                        <img class="tsmth" src="%img%" alt="Weather Icon">
                        <div class="flex flex-column lleft">
                            <p class="top">%time%</p>
                            <p class="bottom">%weather%</p>
                        </div>
                    </div>
                    <div class="splitter"></div>
                    <div class="item-right flex items-center">
                        <div class="flex flex-row rleft">
                            <p><span id="degreesvalue">%temp%</span></p>
                            <p><span id="degrees">°C</span></p>
                        </div>
                        <div class="flex flex-column rright">
                            <p>Max: <span id="degreesvalue">%max%</span><span id="degrees">°C</span></p>
                            <p>Low: <span id="degreesvalue">%low%</span><span id="degrees">°C</span></p>
                        </div>
                    </div>
                </div>
                ';
    // hello and welcome back to my second devlog
    //   10-??-2025 8:48pm - so, i have started rewriting this so moon and sun works, couldnt figure out how to do it in one function, so two
    //   10-??-2025 9:28pm - i got it to work in 4 functions, pretty nice, feel like there is room for optimisation and code smallerance-ish
    //   10-??-2025 10:56pm - SO, after testing multiple itterations, it works!!!!!!!!!!!!!!!!!!!!!!!! all in 3 functions
    //   10-??-2025 11:01pm - P.S. as of writing this, i have 4% left on my laptop as I gave my charger to K. to solder, so i hope tomorrow i can write something.
    //   11-??-2025 8:21pm - hey, i can write... but anyways, it works???, itka
    function posSphere($now, $rise, $set) {
        $riseMin = ttm($rise);
        $setMin = ttm($set);
        $min = ttm($now);

        // this thing is so fucking stupid, but it works
        if ($setMin < $riseMin) {
            $setMin += 1440;
            if ($min < $riseMin) $min += 1440;
        }

        if ($min < $riseMin) return 0;
        if ($min > $setMin) return 126;

        return 126 - round((($min - $riseMin) / ($setMin - $riseMin)) * 126);
    }

    function ttm($time) {
        $parts = explode(" ", $time);
        $timeParts = explode(":", $parts[0]);
        $hour = intval($timeParts[0]);
        $min = intval($timeParts[1]);

        if (strpos($parts[1], "PM") !== false && $hour != 12) $hour += 12;
        if (strpos($parts[1], "AM") !== false && $hour == 12) $hour = 0;
        return $hour * 60 + $min;
    }

    // Obtains the correct image from OpenWeatherMap's API
    function OWMIcons($data) {
        return "//openweathermap.org/img/wn/$data@2x.png";
    }
    
    // Turns the IANA timezone format (Europe/Riga) into readable time
    function dateFromTimezone($timezone, $variant) {
        $date = new DateTime("now", new DateTimeZone($timezone));

        if ($variant == 0) {
            return $date -> format("g:i A");
        } else {
            return $date -> format("d-m-Y");
        }
    }
    
    // Round to first decmial point thing
    function roundFirstD($numb) {
        return round($numb, 1);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>_________ Sky</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- No data found thing -->
    <div class="absolute no-data-found white br<?php
        if ($weatherData != null) {
            echo " hidden";
        }
    ?>">
        <h1>NO DATA!!</h1>
    </div>

    <!-- Header, the thing that's usually in the head (top) -->
    <header class="flex shadow space-between items-center white">

        <!-- Left location, with _________ Sky and City w/ Country-->
        <div class="flex left items-center">
            <svg class="hamb tfour finger" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
            </svg>

            <h1 class="title finger">_________ Sky</h1>

            <img class="loc tfour" src="./img/google-maps.gif" alt="loc">
            <span class="loccity"><?php echo $weatherData["city"]["name"] . ", " . $weatherData["city"]["country"]; ?></span>
        </div>
        <div class="flex center items-center">
            <!-- The actually functioning search bar as of April 11th -->
            <form class="relative">
                <svg class="ttwo absolute" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 2a9 9 0 1 0 6.293 15.293l4.707 4.707a1 1 0 0 0 1.414-1.414l-4.707-4.707A9 9 0 0 0 11 2zM11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14z"></path>
                </svg>
                <input id="thesearchbarofdoom" type="text" placeholder="Search Location" value="<?php 
                    if ($_GET['q'] != null) {
                        echo $_GET['q'];
                    } else {
                        echo "Cēsis";
                    }
                ?>">

                <script>
                    var searchTimeout;
                    document.getElementById('thesearchbarofdoom').onkeypress = function () {
                        if (searchTimeout != undefined) clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(pageRedirect, 500);
                    }

                    function pageRedirect() {
                        window.location = location.protocol + '//' + location.host + "?q=" + document.getElementById('thesearchbarofdoom').value;
                        console.log("redirecting...")
                    }
                </script>

                <div class="bgaaa absolute">
                    <img src="./img/worldwide.gif" alt="w" class="tfour relative">
                </div>
            </form>

            <!-- Dark/light mode button -->
            <div class="button relative finger flex" onclick="darkmode()">
                <svg class="tthree" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"></path>
                </svg> 
                <p>Light</p>
            </div>
        </div>

        <!-- Right location, with the useless and unusable buttons -->
        <div class="right flex">
            <img src="./img/notification.gif" alt="Description of the image" class="finger tfive">
            <img src="./img/settings.gif" alt="Description of the image" class="finger tfive a">
        </div>
    </header>

    <!-- Main content, the thing that almost made me Google Maps the nearest bridge -->
    <main>
        <!-- Left side of the screen. Current weather, time, quality, sun and moon summary  -->
        <div class="left">

            <!-- Section | Quick. Includes weather, time, wind and imperial/metric system change -->
            <div class="section-quick white shadow br box-sizing">
                <div class="sub-weather flex">

                    <!-- Current Weather -->
                    <div>
                        <p class="weather">Current Weather</p>
                        <p class="time">Local time:
                            <?php echo dateFromTimezone($timezone["iana_timezone"], 0); ?>
                        </p>

                        <!-- Whatever was this, I am too lazy to remember, 11:23PM hits CRAZY!! -->
                        <div class="flex items-center">
                            <img class="tsmth" src="<?php echo OWMIcons($weatherData["list"]["0"]["weather"]["0"]["icon"])?>" alt="Weather Icon">
                            <div class="flex items-center cels">
                                <p><span id="degreesvalue"><?php echo roundFirstD($weatherData["list"]["0"]["temp"]["day"])?></span></p>
                                <p><span id="degrees">°C</span></p>
                            </div>
                            <div class="feels flex-column">
                                <p><?php echo $weatherData["list"]["0"]["weather"]["0"]["main"]?></p>
                                <p>
                                    Feels like 
                                    <span id="degreesvalue"><?php echo roundFirstD($weatherData["list"]["0"]["feels_like"]["day"])?></span>
                                    <span id="degrees">°C</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <select id="imsystemselect" class="white br" onchange="imsystem()">
                            <option value="celsius">Celsius and Kilometers</option> 
                            <option value="fahrenheit">Fahrenheit and Miles</option>
                        </select>
                    </div>
                </div>

                <!-- Must of been the gustavs...? -->
                <div>
                    <p class="wind">
                        Current wind speed: 
                        <span id="speedvalue"><?php echo roundFirstD($weatherData["list"]["0"]["speed"])?></span> 
                        <span id="speed">km/h</span>
                    </p>
                </div>
            </div>
        
            <!-- Section | Small Boxes. Includes some more information, useful for other data nerds 🤓🤓🤓 -->
            <div class="section-small box-sizing">

                <!-- Cloud amount -->
                <div class="small white shadow br">
                    <div class="flex">
                        <img src="./img/clouds.gif" alt="Description of the image" class="tfour">
                        <p class="title">Amount of Clouds</p>
                    </div>
                    <p class="value"><?php echo $weatherData["list"]["0"]["clouds"]?></p>
                </div>

                <!-- Pressure -->
                <div class="small white shadow br">
                    <div class="flex">
                        <img src="./img/air-pump.gif" alt="Description of the image" class="tfour">
                        <p class="title">Pressure</p>
                    </div>
                    <p class="value">
                        <?php echo $weatherData["list"]["0"]["pressure"]?>°
                    </p>
                </div>

                <!-- Humidity -->
                <div class="small white shadow br">
                    <div class="flex">
                        <img src="./img/humidity.gif" alt="Description of the image" class="tfour">
                        <p class="title">Humidity</p>
                    </div>
                    <p class="value">
                        <?php echo $weatherData["list"]["0"]["humidity"]?>%
                    </p>
                </div>

                <!-- Chance of Rain -->
                <div class="small white shadow br">
                    <div class="flex">
                        <img src="./img/clouds.gif" alt="Description of the image" class="tfour">
                        <p class="title">Chance of Rain</p>
                    </div>
                    <p class="value">
                        <?php
                            if ($weatherData["list"]["0"]["rain"] == null) {
                                echo "0";
                            } else {
                                echo $weatherData["list"]["0"]["rain"];
                            }
                        ?>%
                    </p>
                </div>

                <!-- Chance of Snow -->
                <div class="small white shadow br">
                    <div class="flex">
                        <img src="./img/clouds.gif" alt="Description of the image" class="tfour">
                        <p class="title">Chance of Snow</p>
                    </div>
                    <p class="value">
                        <?php
                            if ($weatherData["list"]["0"]["snow"] == null) {
                                echo "0";
                            } else {
                                echo $weatherData["list"]["0"]["snow"];
                            }
                        ?>%
                    </p>
                </div>

                <!-- Chance of Gust -->
                <div class="small white shadow br">
                    <div class="flex">
                        <img src="./img/wind.gif" alt="Description of the image" class="tfour">
                        <p class="title">Chance of Gust</p>
                    </div>
                    <p class="value">
                        <?php echo $weatherData["list"]["0"]["gust"]?>%
                    </p>
                </div>
            </div>

            <!-- Section | Sun & Moon Summary. Tells you when sun rises and sets, and shows the distance how much it has traveled. -->
            <div class="relative">
                <div class="section-sunmoon white shadow br box-sizing">
                    <p class="btm">Sun & Moon Summary</p>

                    <!-- Sun -->
                    <div class="flex sun space-between">
                        <div class="flex flex-row">
                            <img class="tsmth" src="./img/sun.gif" alt="">
                            <div class="flex-column quality">
                                <p>Temprature</p>
                                <p>
                                    <span id="degreesvalue"><?php echo roundFirstD($weatherData["list"]["0"]["temp"]["day"])?></span>
                                    <span id="degrees">°C</span>
                                </p>
                            </div>
                        </div>
                        <div class="riseset flex flex-row">
                            <div class="sun-left flex flex-column items-center">
                                <img src="./img/field.gif" alt="Sunrise Icon" class="tfour">
                                <p class="top">Sunrise</p>
                                <p class="bottom">
                                    <?php echo date("g:i A", $weatherData["list"]["0"]["sunrise"]);?>
                                </p>
                            </div>
                            
                            <!-- Stole this from the site itself, has no functionality as I am a lazy fuck. Sorry, not sorry Tom-->
                            <div class="graph">
                                <svg class="hundo" viewBox="0 0 100 50">
                                    <path d="M 10,50 A 40,40 0 1 1 90,50" fill="none" stroke="#e5e5e5" stroke-width="10"></path>
                                    <path d="M 10,50 A 40,40 0 1 1 90,50" fill="none" stroke="#f59e0b" stroke-width="10" stroke-dasharray="126" stroke-dashoffset="<?php 

                                        echo posSphere(dateFromTimezone($timezone["iana_timezone"], 0), date("g:i A", $weatherData["list"]["0"]["sunrise"]), date("g:i A", $weatherData["list"]["0"]["sunset"]));
                
                                    ?>"></path>
                                </svg>
                            </div>

                            <div class="sun-right flex flex-column items-center">
                                <img src="./img/sunset.gif" alt="Sunset Icon" class="tfour">
                                <p class="top">Sunset</p>
                                <p class="bottom">
                                    <?php echo date("g:i A", $weatherData["list"]["0"]["sunset"]);?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Moon -->
                    <div class="flex moon space-between">
                        <div class="flex flex-row">
                            <img class="tsmth" src="./img/moon.gif" alt="">
                            <div class="flex-column quality">
                                <p>Temprature</p>
                                <p>
                                    <span id="degreesvalue"><?php echo roundFirstD($weatherData["list"]["0"]["temp"]["night"])?></span>
                                    <span id="degrees">°C</span>
                                </p>
                            </div>
                        </div>
                        <div class="riseset flex flex-row">
                            <div class="moon-left flex flex-column items-center">
                                <img src="./img/moon-rise.gif" alt="Moonrise Icon" class="tfour">
                                <p class="top">Moonrise</p>
                                <p class="bottom">
                                    <?php echo date("g:i A", $weatherData["list"]["0"]["sunset"]);?>
                                </p>
                            </div>
                            
                            <!-- Stole this from the site itself, has no functionality as I am a lazy fuck. Sorry, not sorry Tom-->
                            <div class="graph">
                                <svg class="hundo" viewBox="0 0 100 50">
                                    <path d="M 10,50 A 40,40 0 1 1 90,50" fill="none" stroke="#e5e5e5" stroke-width="10"></path>
                                    <path d="M 10,50 A 40,40 0 1 1 90,50" fill="none" stroke="#0d92f4" stroke-width="10" stroke-dasharray="126" stroke-dashoffset="<?php 

                                        echo posSphere(dateFromTimezone($timezone["iana_timezone"], 0), date("g:i A", $weatherData["list"]["0"]["sunset"]), date("g:i A", $weatherData["list"]["1"]["sunrise"]));

                                    ?>"></path>
                                </svg>
                            </div>

                            <div class="moon-right flex flex-column items-center">
                                <img src="./img/moon-set.gif" alt="Moonset Icon" class="tfour">
                                <p class="top">Moonset</p>
                                <p class="bottom">
                                    <?php echo date("g:i A", $weatherData["list"]["1"]["sunrise"]);?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side of the screen. Time schedule for today, tomorrow and in the next 10 days -->
        <div class="right">
            <div class="section-schedule relative white shadow br block box-sizing">

                <!-- Buttons -->
                <div class="schedule-buttons">
                    <button id="btn1" class="selected" onclick="schedule(this)">Today</button>
                    <button id="btn2" class="" onclick="schedule(this)">Tomorrow</button>
                    <button id="btn3" class="" onclick="schedule(this)">10 Days</button>
                </div>

                <!-- Display all the items in the schedule. Uses PHP w/ a template, as I am lazy!! -->
                <div class="relative">
                    <div id="today" class="absolute schedule schedule-shown flex">
                        <?php 
                            $edited = $template;
                            
                            $edited = str_replace("%time%", date("d-m-Y", $weatherData["list"]["0"]["dt"]), $edited);
                            $edited = str_replace("%img%", OWMIcons($weatherData["list"]["0"]["weather"]["0"]["icon"]), $edited);
                            $edited = str_replace("%weather%", $weatherData["list"]["0"]["weather"]["0"]["main"], $edited);
                            $edited = str_replace("%temp%", roundFirstD($weatherData["list"][0]["temp"]["day"]), $edited);
                            $edited = str_replace("%max%", roundFirstD($weatherData["list"][0]["temp"]["max"]), $edited);
                            $edited = str_replace("%low%", roundFirstD($weatherData["list"][0]["temp"]["min"]), $edited);

                            echo $edited;
                        ?>
                    </div>
                    <div id="tomorrow" class="absolute schedule schedule-hidden flex">
                        <?php 
                            $edited = $template;

                            $edited = str_replace("%time%", date("d-m-Y", $weatherData["list"]["1"]["dt"]), $edited);
                            $edited = str_replace("%img%", OWMIcons($weatherData["list"]["1"]["weather"]["0"]["icon"]), $edited);
                            $edited = str_replace("%weather%", $weatherData["list"]["1"]["weather"]["0"]["main"], $edited);
                            $edited = str_replace("%temp%", roundFirstD($weatherData["list"][1]["temp"]["day"]), $edited);
                            $edited = str_replace("%max%", roundFirstD($weatherData["list"][1]["temp"]["max"]), $edited);
                            $edited = str_replace("%low%", roundFirstD($weatherData["list"][1]["temp"]["min"]), $edited);

                            echo $edited;
                        ?>
                    </div>
                    <div id="tendays" class="absolute schedule schedule-hidden flex">
                        <?php
                            for ($i = 2; $i <= 12; $i++) {
                                $edited = $template;
                                
                                $edited = str_replace("%time%", date("d-m-Y", $weatherData["list"][$i]["dt"]), $edited);
                                $edited = str_replace("%img%", OWMIcons($weatherData["list"][$i]["weather"]["0"]["icon"]), $edited);
                                $edited = str_replace("%weather%", $weatherData["list"][$i]["weather"]["0"]["main"], $edited);
                                $edited = str_replace("%temp%", roundFirstD($weatherData["list"][$i]["temp"]["day"]), $edited);
                                $edited = str_replace("%max%", roundFirstD($weatherData["list"][$i]["temp"]["max"]), $edited);
                                $edited = str_replace("%low%", roundFirstD($weatherData["list"][$i]["temp"]["min"]), $edited);

                                echo $edited;
                            }
                        ?>
                    </div>
                </div>

                <!-- I see no use in a little arrow at the bottom, but it's there! -->
                <div class="absolute tfive schedule-bottom">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-7 h-7 text-gray-500">
                        <path d="M12 16l-6-6h12l-6 6z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Javascript, as I don't understand how PHP integration works -->
    <script>
        var ldv = document.querySelectorAll("span[id='degreesvalue']")
        var lsv = document.querySelectorAll("span[id='speedvalue']")
        var ldisv = document.querySelectorAll("span[id='distancevalue']")

        var dd_ldv = Array.from(ldv).map(item => item.innerHTML);
        var dd_lsv = Array.from(lsv).map(item => item.innerHTML);
        var dd_ldisv = Array.from(ldisv).map(item => item.innerHTML);

        var rootstyle = document.querySelector(':root');
        var dark = false;
        
        // Schedule functionality
        function schedule(self) {
            document.getElementById("btn1").classList.remove("selected")
            document.getElementById("btn2").classList.remove("selected")
            document.getElementById("btn3").classList.remove("selected")

            self.classList.add("selected")

            let jsonButtons = { 
                "btn1": document.getElementById("today"),
                "btn2": document.getElementById("tomorrow"),
                "btn3": document.getElementById("tendays")
            }

            Object.values(jsonButtons).forEach(schedule => {
                schedule.classList.remove("schedule-shown");
                schedule.classList.add("schedule-hidden");
            });

            jsonButtons[self.id].classList.remove("schedule-hidden")
            jsonButtons[self.id].classList.add("schedule-shown")
        }

        // Dark mode functionality w/ toggle
        function darkmode() {
            if (dark == false) {
                rootstyle.style.setProperty('--bg-2', 'rgb(31 41 55)');
                rootstyle.style.setProperty('--bg-1', 'rgb(75 85 99)');

                rootstyle.style.setProperty('--text-1', '#fff');
                rootstyle.style.setProperty('--text-2', '#fff');
                rootstyle.style.setProperty('--text-3', '#fff');
                rootstyle.style.setProperty('--text-4', '#fff');

                rootstyle.style.setProperty('--select-bg', 'rgb(55 65 81)');
                rootstyle.style.setProperty('--select-border','rgb(107 114 128)');

                dark = true
            } else {
                rootstyle.style.setProperty('--bg-1', '#fff');
                rootstyle.style.setProperty('--bg-2', 'rgb(243 244 246)');

                rootstyle.style.setProperty('--text-1', '#000');
                rootstyle.style.setProperty('--text-2', 'rgb(31 41 55)');
                rootstyle.style.setProperty('--text-3', '#384454');
                rootstyle.style.setProperty('--text-4', 'rgb(75 85 99)');

                rootstyle.style.setProperty('--select-bg', '#fff');
                rootstyle.style.setProperty('--select-border','rgb(209 213 219)');

                dark = false
            }
        }
        
        // Imperial Metric system functionality
        function imsystem() {
            var value = document.getElementById("imsystemselect").value

            // Changes the endings to the correct parameters
            document.querySelectorAll("span[id='degrees']").forEach(item => {
                if (item.innerHTML == "N/A") return; 
                
                if (value == "celsius") {
                    item.innerHTML = "°C"
                } else if (value == "fahrenheit") {
                    item.innerHTML = "°F"
                } else {
                    item.innerHTML = "N/A"
                }
            })

            document.querySelectorAll("span[id='speed']").forEach(item => {
                if (item.innerHTML == "N/A") return; 

                if (value == "celsius") {
                    item.innerHTML = "km/h"
                } else if (value == "fahrenheit") {
                    item.innerHTML = "mi/h"
                } else {
                    item.innerHTML = "N/A"
                }
            })

            document.querySelectorAll("span[id='distance']").forEach(item => {
                if (item.innerHTML == "N/A") return; 
                
                if (value == "celsius") {
                    item.innerHTML = "km"
                } else if (value == "fahrenheit") {
                    item.innerHTML = "mi"
                } else {
                    item.innerHTML = "N/A"
                }
            })
            
            ldv.forEach((item, index) => {
                if (item.innerHTML == "N/A") return; 

                if (value == "celsius") {
                    item.innerHTML = dd_ldv[index]
                } else if (value == "fahrenheit") {
                    item.innerHTML = ((item.innerHTML*9/5)+32).toFixed(1)
                } else {
                    item.innerHTML = "N/A"
                }
            });

            lsv.forEach((item, index) => {
                if (item.innerHTML == "N/A") return; 

                if (value == "celsius") {
                    item.innerHTML = dd_lsv[index]
                } else if (value == "fahrenheit") {
                    item.innerHTML = (item.innerHTML / 1.609344).toFixed(1)
                } else {
                    item.innerHTML = "N/A"
                }
            })

            ldisv.forEach((item, index) => {
                if (item.innerHTML == "N/A") return; 

                if (value == "celsius") {
                    item.innerHTML = dd_ldisv[index];
                } else if (value == "fahrenheit") {
                    item.innerHTML = (item.innerHTML/1.609344).toFixed(1)
                } else {
                    item.innerHTML = "N/A"
                }
            })
        }

        // It is called hell, because that's what I call HTML tags
        function hell() { 
            document.getElementById("imsystemselect").value = "celsius"

            // I had an issue with it not changing when the page loads, so this is the fallback option.
            if (document.getElementById("imsystemselect").value == "fahrenheit") {
                imsystem()
            }
        }
        hell()
    </script>
</body>
</html>