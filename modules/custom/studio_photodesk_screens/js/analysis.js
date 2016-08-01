

(function ($) {


var lineChartCanvas = document.getElementById("uploadTrendsChart").getContext("2d");
var lineChart = new Chart(lineChartCanvas);

var lineChartData = {
  labels: [{% for item in period %} "{{ item.nid }}", {% endfor %}],
  datasets: [
    {
      label: "Time per Product",
        fillColor: "rgba(220,220,220,0.5)",
        strokeColor: "rgba(220,220,220,1)",
        pointColor: "rgba(220,220,220,1)",
        pointStrokeColor: "#fff",
        pointHighlightFill: "#fff",
        pointHighlightStroke: "rgba(220,220,220,1)",
      data: [{% for item in period %} "{{ item.time }}", {% endfor %}],
    }
  ]
};

var lineChartOptions = {
  //Boolean - If we should show the scale at all
    showScale: true,
    //Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines: true,
    //String - Colour of the grid lines
    scaleGridLineColor: "rgba(0, 0, 0,.05)",
    //Number - Width of the grid lines
    scaleGridLineWidth: 1,
    //Boolean - Whether to show horizontal lines (except X axis)
    scaleShowHorizontalLines: true,
    //Boolean - Whether to show vertical lines (except Y axis)
    scaleShowVerticalLines: true,
    //Boolean - Whether the line is curved between points
    bezierCurve: true,
    //Number - Tension of the bezier curve between points
    bezierCurveTension: 0.4,
    //Boolean - Whether to show a dot for each point
    pointDot: true,
    //Number - Radius of each point dot in pixels
    pointDotRadius: 3,
    //Number - Pixel width of point dot stroke
    pointDotStrokeWidth: 1,
    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
    pointHitDetectionRadius: 20,
    //Boolean - Whether to show a stroke for datasets
    datasetStroke: true,
    //Number - Pixel width of dataset stroke
    datasetStrokeWidth: 2,
    //Boolean - Whether to fill the dataset with a color
    datasetFill: true,
    //String - A legend template
    //Boolean - whether to make the chart responsive to window resizing
    responsive: true
};

//Create the line chart
var uploadTrend = lineChart.Line(lineChartData, lineChartOptions);

jQuery("#tab-analysis").click(function () {
  alert('yes');
    uploadTrend.destroy();
    var uploadTrend = lineChart.Line(lineChartData, lineChartOptions);
});

}(jQuery));
