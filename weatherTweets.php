<!DOCTYPE html>
<html>
  <head>
    <meta charset ="utf-8">
    <title>Weather Tweets</title>
    <link rel="stylesheet" type="text/css" href="bootstrap-3.2.0-dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="bootstrap-3.2.0-dist/css/bootstrap-theme.min.css">
    <script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
    <script type="text/javascript" src="bootstrap-3.2.0-dist/js/bootstrap.min.js"></script>

  </head>

  <body>
    <div class="container">                
      <h3>Get an overview of what people are tweeting about the weather</h3>
          
        <div class="row">
            
          <div class="col-sm-6 col-md-4 col lg-2">
            
            <form name="picker" method="post">
              
              <div class="btn-group">
          
                <input name="location" type="text" class="span3" placeholder="Type Name of City...">
              </div>
            </form>
                  
            <?php
            
              //call "openweather" to get geo-location
              if(isset($_POST['location'])){
          
                $city = $_POST['location'];
                $country = "IE";
                $url = "http://api.openweathermap.org/data/2.5/weather?q=".$city.","."&units=metric&cnt=7&lang=en";
                $json = file_get_contents($url);
                $data = json_decode($json,TRUE);
              }
          
              //call twitter api
              if(isset($_POST['location'])){
                //start a session
                session_start();

                //Path to twitteroauth library
                require_once("twitteroauth/twitteroauth.php");

                //the type of query you want to perform via the twitter api
                $url = "https://api.twitter.com/1.1/search/tweets.json";

                //hash tag in a URL friendly format
                $search = "?q=";
                $hashtag = "%23";

                //the query term you are looking for
                $queryPos = array("good","great","nice","fine","lovely","beautiful","wonderful","excellent","gorgeous","fair",
                                "pleasant","sunny","sun","sunshine","warm","hot","mild","dry","clear","bright");
                $queryNeg = array("bad","awful","terrible","nasty","lousy","foul","rotten","miserable","unpleasant","dull",
                                "cool","chilly","cold","freezing","icy","frosty","rainy","rain","wet","foggy",
                                "misty","windy","stormy","breezy","cloudy","overcast","cloud");
                
                //OR operand
                $orQuery = "+OR+";
                
                //compile complete search query
                $mainQuery = $search.$queryPos[0].$orQuery.$queryNeg[0];
                for($i=1 ; $i<count($queryPos) ; $i++){
                  $mainQuery .= $orQuery.$queryPos[$i];
                }
                for($k=1 ; $k<count($queryNeg) ; $k++){
                  $mainQuery .= $orQuery.$queryNeg[$k];
                }
                
                //how many items do you want back?
                $count = "&count=100";

                //specify language = english
                $lang = "&lang=en";

                //add location
                $loc = "&geocode=" . $data[coord][lat] . "," . $data[coord][lon] . ",2mi";

                //creates the full url for the connection to use
                $fqurl = $url.$mainQuery.$count.$lang.$loc."&since=".date(Y/m/d);

                //api access
                $consumerkey = getenv('CON_KEY');
                $consumersecret = getenv('CON_SEC'); 
                $accesstoken = getenv('ACC_TOK'); 
                $accesstokensecret = getenv('ACC_SEC'); 

                //generates the access tokens
                function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
                $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
                return $connection;
                }

                //using the access token, access the twitter api with your api access info
                $connection = getConnectionWithAccessToken($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);

                //make the connection. "->stauses" parses into the document before making an object
                $tweets = $connection->get($fqurl)->statuses;
                
                //counters for pos and neg tweets
                $positiveTweets = 0;
                $negativeTweets = 0;

                //loop through tweets checking for each word
                foreach($tweets as $twit){
                  for($x=0 ; $x<count($queryPos) ; $x++){
                   if(strpos($twit->text, $queryPos[$x]) != FALSE){
                     $positiveTweets++;
                   }
                  }
                  for($y=0 ; $y<count($queryNeg) ; $y++){
                    if(strpos($twit->text, $queryNeg[$y]) != FALSE){
                      $negativeTweets++;
                    }
                  }
                }

                $allTweets = $positiveTweets + $negativeTweets;
                $notInQuery = 100 - $allTweets;

                echo "Positive Tweets About " . ucwords($_POST['location']) . ": " . $positiveTweets . "<br/>";
                echo "Negative Tweets About " . ucwords($_POST['location']) . ": " . $negativeTweets . "<br/>";
              }
            ?>
          </div>
        </div>
                  
          <script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
          <script src="highcharts/highcharts.js"></script>
          <div id="chart"></div>
    
          <script type="text/javascript">
            $(function() {
              $(document).ready(function(){

                $("#container").highcharts({

                  chart: {                        
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false
                  },
                  title: {
                    //get location from input
                    text: '<?php echo ucwords($_POST['location']);?>'
                  },
                  tooltip: {
                    pointFormat:'{series.name}: <b>{point.percentage:.1f}%</b>'                      
                  },
                  plotOptions: {
                    pie: {
                      allowPointSelect: true,
                      cursor: 'pointer',
                      dataLabels: {
                          enabled: true,
                      },
                      showInLegend:true
                    }
                  },
                  series: [{
                    //data for pie chart
                    type: 'pie',
                    name: 'Positivity/Negativity',
                    data: [
                      ['Positive', '12'],
                      ['Negative', '21'],
                      ['Not In Query Parameters', '3']
                    ]
                  }]
                });
              });
            });
          </script>
          <script src="highcharts/highcharts.js"></script>
          <script src="highcharts/modules/exporting.js"></script>
          <div id="container" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto"></div>
        </div>
    </div>
  </body>
</html>
