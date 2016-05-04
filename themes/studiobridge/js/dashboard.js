jQuery(document).ready(function($) {

var data2 = [
    [gd(2012, 1, 1), 7], [gd(2012, 1, 2), 6], [gd(2012, 1, 3), 4], [gd(2012, 1, 4), 8],
    [gd(2012, 1, 5), 9], [gd(2012, 1, 6), 7], [gd(2012, 1, 7), 5]
];

var data3 = [
    [gd(2012, 1, 1), 10], [gd(2012, 1, 2), 12], [gd(2012, 1, 3), 24], [gd(2012, 1, 4), 9],
    [gd(2012, 1, 5), 14], [gd(2012, 1, 6), 6], [gd(2012, 1, 7), 20]
];


var dataset = [
    {
        label: "This Week",
        data: data3,

        lines: {
            show: false,
            fill: true
        },
        splines: {
            show: true,
            tension: 0.4,
            lineWidth: 1,
            fill: 0.4
        },
        points: {
            radius: 0,
            show: true
        },
        shadowSize: 2

    }, {
        label: "Last Week",
        data: data2,
        yaxis: 2,

        lines: {
            show: false,
            fill: true
        },
        splines: {
            show: true,
            tension: 0.4,
            lineWidth: 1,
            fill: 0.4
        },
        points: {
            radius: 0,
            show: true
        },
        shadowSize: 2
    }
];


var options = {
    colors: ["#1ab394", "#1C84C6"],
    xaxis: {
      ticks: 0
    },
    yaxes: [{
      ticks: 0
    },{
      ticks: 0
    }
    ],
    legend: {
        noColumns: 1,
        labelBoxBorderColor: "#000000",
        position: "nw"
    },
    grid: {
        hoverable: false,
        borderWidth: 0
    },
    tooltip: true
};

function gd(year, month, day) {
    return new Date(year, month - 1, day).getTime();
}

var previousPoint = null, previousLabel = null;

$.plot($("#flot-dashboard-chart"), dataset, options);

});
