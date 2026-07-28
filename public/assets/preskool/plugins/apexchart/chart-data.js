'use strict';

$(document).ready(function() {

	function generateData(baseval, count, yrange) {
		var i = 0;
		var series = [];
		while (i < count) {
			var x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;;
			var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
			var z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;

			series.push([x, y, z]);
			baseval += 86400000;
			i++;
		}
		return series;
	}


	// Column chart
    if($('#sales_chart').length > 0 ){
    	var columnCtx = document.getElementById("sales_chart"),
    	columnConfig = {
    		colors: ['#7638ff', '#fda600'],
    		series: [
    			{
    			name: "Received",
    			type: "column",
    			data: [70, 150, 80, 180, 150, 175, 201, 60, 200, 120, 190, 160, 50]
    			},
    			{
    			name: "Pending",
    			type: "column",
    			data: [23, 42, 35, 27, 43, 22, 17, 31, 22, 22, 12, 16, 80]
    			}
    		],
    		chart: {
    			type: 'bar',
    			fontFamily: 'Poppins, sans-serif',
    			height: 350,
    			toolbar: {
    				show: false
    			}
    		},
    		plotOptions: {
    			bar: {
    				horizontal: false,
    				columnWidth: '60%',
    				endingShape: 'rounded'
    			},
    		},
    		dataLabels: {
    			enabled: false
    		},
    		stroke: {
    			show: true,
    			width: 2,
    			colors: ['transparent']
    		},
    		xaxis: {
    			categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
    		},
    		yaxis: {
    			title: {
    				text: '$ (thousands)'
    			}
    		},
    		fill: {
    			opacity: 1
    		},
    		tooltip: {
    			y: {
    				formatter: function (val) {
    					return "$ " + val + " thousands"
    				}
    			}
    		}
    	};
    	var columnChart = new ApexCharts(columnCtx, columnConfig);
    	columnChart.render();
    }

	//Pie Chart
    if($('#invoice_chart').length > 0 ){
    	var pieCtx = document.getElementById("invoice_chart"),
    	pieConfig = {
    		colors: ['#7638ff', '#ff737b', '#fda600', '#1ec1b0'],
    		series: [55, 40, 20, 10],
    		chart: {
    			fontFamily: 'Poppins, sans-serif',
    			height: 350,
    			type: 'donut',
    		},
    		labels: ['Paid', 'Unpaid', 'Overdue', 'Draft'],
    		legend: {show: false},
    		responsive: [{
    			breakpoint: 480,
    			options: {
    				chart: {
    					width: 200
    				},
    				legend: {
    					position: 'bottom'
    				}
    			}
    		}]
    	};
    	var pieChart = new ApexCharts(pieCtx, pieConfig);
    	pieChart.render();
	}
	
	// Simple Line
    if($('#s-line').length > 0 ){
    var sline = {
      chart: {
        height: 350,
        type: 'line',
        zoom: {
          enabled: false
        },
        toolbar: {
          show: false,
        }
      },
      // colors: ['#4361ee'],
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'straight'
      },
      series: [{
        name: "Desktops",
        data: [10, 41, 35, 51, 49, 62, 69, 91, 148]
      }],
      title: {
        text: 'Product Trends by Month',
        align: 'left'
      },
      grid: {
        row: {
          colors: ['#f1f2f3', 'transparent'], // takes an array which will be repeated on columns
          opacity: 0.5
        },
      },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
      }
    }

    var chart = new ApexCharts(
      document.querySelector("#s-line"),
      sline
    );

    chart.render();
    }


// Simple Line Area
 if($('#s-line-area').length > 0 ){
var sLineArea = {
    chart: {
        height: 350,
        type: 'area',
        toolbar: {
          show: false,
        }
    },
    // colors: ['#4361ee', '#888ea8'],
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    series: [{
        name: 'series1',
        data: [31, 40, 28, 51, 42, 109, 100]
    }, {
        name: 'series2',
        data: [11, 32, 45, 32, 34, 52, 41]
    }],

    xaxis: {
        type: 'datetime',
        categories: ["2018-09-19T00:00:00", "2018-09-19T01:30:00", "2018-09-19T02:30:00", "2018-09-19T03:30:00", "2018-09-19T04:30:00", "2018-09-19T05:30:00", "2018-09-19T06:30:00"],                
    },
    tooltip: {
        x: {
            format: 'dd/MM/yy HH:mm'
        },
    }
}

var chart = new ApexCharts(
    document.querySelector("#s-line-area"),
    sLineArea
);

chart.render();
}

// Simple Column
if($('#s-col').length > 0 ){
var sCol = {
    chart: {
        height: 350,
        type: 'bar',
        toolbar: {
          show: false,
        }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'  
        },
    },
    // colors: ['#888ea8', '#4361ee'],
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    series: [{
        name: 'Net Profit',
        data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
    }, {
        name: 'Revenue',
        data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
    }],
    xaxis: {
        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
    },
    yaxis: {
        title: {
            text: '$ (thousands)'
        }
    },
    fill: {
        opacity: 1

    },
    tooltip: {
        y: {
            formatter: function (val) {
                return "$ " + val + " thousands"
            }
        }
    }
}

var chart = new ApexCharts(
    document.querySelector("#s-col"),
    sCol
);

chart.render();
}


// Simple Column Stacked
if($('#s-col-stacked').length > 0 ){
var sColStacked = {
    chart: {
        height: 350,
        type: 'bar',
        stacked: true,
        toolbar: {
          show: false,
        }
    },
    // colors: ['#4361ee', '#888ea8', '#e3e4eb', '#d3d3d3'],
    responsive: [{
        breakpoint: 480,
        options: {
            legend: {
                position: 'bottom',
                offsetX: -10,
                offsetY: 0
            }
        }
    }],
    plotOptions: {
        bar: {
            horizontal: false,
        },
    },
    series: [{
        name: 'PRODUCT A',
        data: [44, 55, 41, 67, 22, 43]
    },{
        name: 'PRODUCT B',
        data: [13, 23, 20, 8, 13, 27]
    },{
        name: 'PRODUCT C',
        data: [11, 17, 15, 15, 21, 14]
    },{
        name: 'PRODUCT D',
        data: [21, 7, 25, 13, 22, 8]
    }],
    xaxis: {
        type: 'datetime',
        categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT', '01/05/2011 GMT', '01/06/2011 GMT'],
    },
    legend: {
        position: 'right',
        offsetY: 40
    },
    fill: {
        opacity: 1
    },
}

var chart = new ApexCharts(
    document.querySelector("#s-col-stacked"),
    sColStacked
);

chart.render();
}

// Simple Bar
if($('#s-bar').length > 0 ){
var sBar = {
    chart: {
        height: 350,
        type: 'bar',
        toolbar: {
          show: false,
        }
    },
    // colors: ['#4361ee'],
    plotOptions: {
        bar: {
            horizontal: true,
        }
    },
    dataLabels: {
        enabled: false
    },
    series: [{
        data: [400, 430, 448, 470, 540, 580, 690, 1100, 1200, 1380]
    }],
    xaxis: {
        categories: ['South Korea', 'Canada', 'United Kingdom', 'Netherlands', 'Italy', 'France', 'Japan', 'United States', 'China', 'Germany'],
    }
}

var chart = new ApexCharts(
    document.querySelector("#s-bar"),
    sBar
);

chart.render();
}

// Mixed Chart
if($('#mixed-chart').length > 0 ){
var options = {
  chart: {
    height: 350,
    type: 'line',
    toolbar: {
      show: false,
    }
  },
  // colors: ['#4361ee', '#888ea8'],
  series: [{
    name: 'Website Blog',
    type: 'column',
    data: [440, 505, 414, 671, 227, 413, 201, 352, 752, 320, 257, 160]
  }, {
    name: 'Social Media',
    type: 'line',
    data: [23, 42, 35, 27, 43, 22, 17, 31, 22, 22, 12, 16]
  }],
  stroke: {
    width: [0, 4]
  },
  title: {
    text: 'Traffic Sources'
  },
  labels: ['01 Jan 2001', '02 Jan 2001', '03 Jan 2001', '04 Jan 2001', '05 Jan 2001', '06 Jan 2001', '07 Jan 2001', '08 Jan 2001', '09 Jan 2001', '10 Jan 2001', '11 Jan 2001', '12 Jan 2001'],
  xaxis: {
    type: 'datetime'
  },
  yaxis: [{
    title: {
      text: 'Website Blog',
    },

  }, {
    opposite: true,
    title: {
      text: 'Social Media'
    }
  }]

}

var chart = new ApexCharts(
  document.querySelector("#mixed-chart"),
  options
);

chart.render();
}

// Donut Chart

if($('#donut-chart').length > 0 ){
var donutChart = {
    chart: {
        height: 350,
        type: 'donut',
        toolbar: {
          show: false,
        }
    },
    // colors: ['#4361ee', '#888ea8', '#e3e4eb', '#d3d3d3'],
    series: [44, 55, 41, 17],
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
}

var donut = new ApexCharts(
    document.querySelector("#donut-chart"),
    donutChart
);

donut.render();
}

// Radial Chart
if($('#radial-chart').length > 0 ){
var radialChart = {
    chart: {
        height: 350,
        type: 'radialBar',
        toolbar: {
          show: false,
        }
    },
    // colors: ['#4361ee', '#888ea8', '#e3e4eb', '#d3d3d3'],
    plotOptions: {
        radialBar: {
            dataLabels: {
                name: {
                    fontSize: '22px',
                },
                value: {
                    fontSize: '16px',
                },
                total: {
                    show: true,
                    label: 'Total',
                    formatter: function (w) {
                        return 249
                    }
                }
            }
        }
    },
    series: [44, 55, 67, 83],
    labels: ['Apples', 'Oranges', 'Bananas', 'Berries'],    
}

var chart = new ApexCharts(
    document.querySelector("#radial-chart"),
    radialChart
);

chart.render();
}	
	
if($('#sales_charts').length > 0) {
	var options = {
		series: [{
		name: 'Sales',
		data: [130, 210, 300, 290, 150, 50, 210, 280, 105],
	  }, {
		name: 'Purchase',
		data: [-150, -90, -50, -180, -50, -70, -100, -90, -105]
	  }],
	  colors: ['#28C76F', '#EA5455'],
		chart: {
		type: 'bar',
		height: 320,
		stacked: true,
		
		zoom: {
		  enabled: true
		}
	  },
	  responsive: [{
		breakpoint: 280,
		options: {
		  legend: {
			position: 'bottom',
			offsetY: 0
		  }
		}
	  }],
	  plotOptions: {
		bar: {
		  horizontal: false,
          borderRadius: 4,
        borderRadiusApplication: "end", // "around" / "end" 
        borderRadiusWhenStacked: "all", // "all"/"last"
		  columnWidth: '20%',
		},
	  },
      dataLabels: {
      enabled: false
    },
      yaxis: {
          min: -200,
          max: 300,
          tickAmount: 5,
        },
	  xaxis: {
		categories: [' Jan ', 'Feb', 'Mar', 'Apr',
		  'May', 'Jun' , 'Jul' , 'Aug', 'Sep'
		],
	  },
	  legend: {show: false},
	  fill: {
		opacity: 1
	  }
	  };

	  var chart = new ApexCharts(document.querySelector("#sales_charts"), options);
	  chart.render();
	}

    if($('#sales-analysis').length > 0 ){
    var options = {
      series: [{
        name: "Sales Analysis",
        data: [25, 30, 18, 15, 22, 20, 30, 20, 22, 18, 15, 20]
    }],
      chart: {
      height: 273,
      type: 'area',
      zoom: {
        enabled: false
      }
    },
    colors: ['#FF9F43'],
    dataLabels: {
      enabled: false
    },
    stroke: {
      curve: 'straight'
    },
    title: {
      text: '',
      align: 'left'
    },
    xaxis: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    },
    yaxis: {
      min: 10,
      max: 60,
      tickAmount: 5,
          labels: {
            formatter: (val) => {
              return val / 1 + 'K'
            }
          }
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left'
        }
    };

    var chart = new ApexCharts(document.querySelector("#sales-analysis"), options);
    chart.render();
  }

  // Student Chart

  if($('#student-chart').length > 0 ){
    var donutChart = {
        chart: {
            height: 260,
            type: 'donut',
            toolbar: {
              show: false,
            }
        },
        colors: ['#3D5EE1', '#6FCCD8'],
        series: [3610, 44],
        labels: ['Present', 'Absent'],
        legend: {show: false},
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                  height: 180,
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    }

    var donut = new ApexCharts(
      document.querySelector("#student-chart"),
      donutChart
  );

  donut.render();
  }

  // Student Chart

  if($('#teacher-chart').length > 0 ){
    var donutChart = {
        chart: {
            height: 260,
            type: 'donut',
            toolbar: {
              show: false,
            }
        },
        colors: ['#3D5EE1', '#6FCCD8'],
        series: [346, 54],
        labels: ['Present', 'Absent'],
        legend: {show: false},
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                  height: 180,
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    }

    var donut = new ApexCharts(
      document.querySelector("#teacher-chart"),
      donutChart
  );

  donut.render();
  }


  // Student Chart

  if($('#staff-chart').length > 0 ){
    var donutChart = {
        chart: {
            height: 260,
            type: 'donut',
            toolbar: {
              show: false,
            }
        },
        colors: ['#3D5EE1', '#6FCCD8'],
        series: [620, 80],
        labels: ['Present', 'Absent'],
        legend: {show: false},
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                  height: 180,
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    }

    var donut = new ApexCharts(
      document.querySelector("#staff-chart"),
      donutChart
  );

  donut.render();
  }


  // Class Chart

  if($('#class-chart').length > 0 ){
    var donutChart = {
        chart: {
            height: 130,
            type: 'donut',
            toolbar: {
              show: false,
            },
            sparkline: {
              enabled: true
            }
        },
        colors: ['#3D5EE1', '#EAB300', '#E82646'],
        series: [45, 11, 2],
        labels: ['Good', 'Average', 'Below Average'],
        legend: {show: false},
        dataLabels: {
              enabled: false
          },
        yaxis: {
          tickAmount: 3,
          labels: {
            offsetX: -15,
          },
        },
        grid: {
          padding: {
            left: -8,
          },
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    }

    var donut = new ApexCharts(
      document.querySelector("#class-chart"),
      donutChart
  );

donut.render();
}

// Student Attendance Chart

if ($('#attendance_chart').length > 0) {
  var donutChart = {
      chart: {
          height: 255,
          type: 'donut',
          toolbar: {
            show: false,
          }
      },
     plotOptions: {
          bar: {
              horizontal: false,
              columnWidth: '50%'
          },
      },
    dataLabels: {
          enabled: false
      },
    
      series: [60,5,15,20],
    labels: [
          'Present',
          'Late',
          'Half Day',
          'Absent'
      ],
      colors:['#1ABE17','#1170E4','#E9EDF4','#E82646'],
      responsive: [{
          breakpoint: 480,
          options: {
              chart: {
                  width: 200
              },
              legend: {
                  position: 'bottom'
              }
          }
      }],
    legend: {
          position: 'bottom',
      }
  }
  
  var donut = new ApexCharts(
      document.querySelector("#attendance_chart"),
      donutChart
  );
  
  donut.render();
  }

// Fees Chart

if($('#fees-chart').length > 0 ){
  var sCol = {
      chart: {
          height: 275,
          type: 'bar',
          stacked: true,
          toolbar: {
            show: false,
          }
      },
      legend: {
        show: true,
         horizontalAlign: 'left',
         position: 'top',
         fontSize: '14px',
         labels: {
          colors: '#5D6369',
         }
      },
      plotOptions: {
          bar: {
              horizontal: false,
              columnWidth: '50%',
              endingShape: 'rounded'  
          },
      },
      colors: ['#3D5EE1', '#E9EDF4'],
      dataLabels: {
          enabled: false
      },
      stroke: {
          show: true,
          width: 2,
          colors: ['transparent']
      },
      grid: {
        padding: {
          left: -8,
        },
      },
      series: [{
          name: 'Collected Fee',
          data: [30, 40,  38, 40, 38, 30, 35, 38, 40]
      }, {
          name: 'Total Fee',
          data: [45, 50, 48, 50, 48, 40, 40, 50, 55]
      }],
      xaxis: {
          categories: ['Q1: 2023', 'Q1: 2023', 'Q1: 2023', 'Q1: 2023', 'Q1: 2023', 'uQ1: 2023l', 'Q1: 2023', 'Q1: 2023', 'Q1: 2023'],
      },
      yaxis: {
      },
      yaxis: {
        tickAmount: 3,
        labels: {
          offsetX: -15
        },
      },
      fill: {
          opacity: 1
  
      },
      tooltip: {
          y: {
              formatter: function (val) {
                  return "$ " + val + " thousands"
              }
          }
      }
  }
  
  var chart = new ApexCharts(
      document.querySelector("#fees-chart"),
      sCol
  );
  
  chart.render();
  }

  if ($('#exam-result-chart').length > 0) {
    var options = {
      chart: {
        type: 'bar',
        height: 310
      },
      series: [{
        name: 'Marks',
        data: [100, 92, 90, 82, 90] // Corresponding scores for Maths, Physics, Chemistry, English, Spanish
      }],
      xaxis: {
        categories: ['Mat', 'Phy', 'Che', 'Eng', 'Sci']
      },
      plotOptions: {
        bar: {
          distributed: true,
          columnWidth: '50%',
          colors: {
            backgroundBarColors: ['#E9EDF4', '#fff'],
            backgroundBarOpacity: 1,
            backgroundBarRadius: 5,
          },
          dataLabels: {
            position: 'top'
          },
        }
      },
      colors: ['#E9EDF4', '#3D5EE1', '#E9EDF4', '#E9EDF4', '#E9EDF4'], // Set specific colors for each bar
      tooltip: {
        y: {
          formatter: function (val) {
            return val + "%"
          }
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val + "%";
        },
        offsetY: -20,
        style: {
          fontSize: '14px',
          colors: ["#304758"]
        }
      },
      grid: {
        yaxis: {
          lines: {
            show: false
          }
        },
      },
      
      legend: {
        show: false
      }
    }
  
    var chart = new ApexCharts(document.querySelector("#exam-result-chart"), options);
    chart.render();
  }

  if ($('#performance_chart').length > 0) {
    var options = {
      chart: {
        type: 'area',
        height: 355
      },
      series: [{
        name: 'Avg. Exam Score',
        data: [75, 68, 65, 68, 75] // Sample data
      }, {
        name: 'Avg. Attendance',
        data: [85, 78, 75, 78, 85] // Sample data
      }],
      xaxis: {
        categories: ['Quarter 1', 'Quarter 2', 'Half yearly', 'Model', 'Final']
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return val + "%";
          }
        },
        shared: true,
        intersect: false,
        custom: function({series, seriesIndex, dataPointIndex, w}) {
          return `<div class="apexcharts-tooltip">${w.globals.labels[dataPointIndex]}<br>Exam Score: <span style="color: #1E90FF;">${series[0][dataPointIndex]}%</span><br>Attendance: <span style="color: #00BFFF;">${series[1][dataPointIndex]}%</span></div>`;
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth'
      },
      grid: {
        padding: {
          left: -15,
          right: 0,
        },
      },
      grid: {   
        yaxis: {
          axisTicks: {
            show: true,
            borderType: 'solid',
            color: '#78909C',
            width: 6,
            offsetX: 0,
            offsetY: 0
        },
          
        },
      },
        yaxis: {
          labels: {
            offsetX: -15
          },
        },
      markers: {
        size: 5,
        colors: ['#1E90FF', '#00BFFF'],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: {
          size: 7
        }
      },
      colors: ['#3D5EE1', '#6FCCD8'], // Color for the lines
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.7,
          opacityTo: 0.9,
          stops: [0, 90, 100]
        }
      },
      legend: {
        position: 'bottom',
        horizontalAlign: 'center'
      }
    }
    var chart = new ApexCharts(document.querySelector("#performance_chart"), options);
    chart.render();
  }

  // Plan Chart

if ($('#plan_chart').length > 0) {
  var donutChart = {
      chart: {
          height: 90,
          type: 'donut',
          toolbar: {
            show: false,
          }, 
          sparkline: {
            enabled: true
          }
      },
      grid: {
        show: false,
        padding: {
          left: 0,
          right: 0
        }
       },
     plotOptions: {
          bar: {
              horizontal: false,
              columnWidth: '50%'
          },
      },
    dataLabels: {
          enabled: false
      },
    
      series: [95,5],
    labels: [
          'Completed',
          'Pending'

      ],
      legend: {show: false},
      colors:['#3D5EE1','#E82646'],
      responsive: [{
          breakpoint: 480,
          options: {
              chart: {
                  width: 100
              },
              legend: {
                  position: 'bottom'
              }
          }
      }],
    legend: {
          position: 'bottom'
      }
  }
  
  var donut = new ApexCharts(
      document.querySelector("#plan_chart"),
      donutChart
  );
  
  donut.render();
  }
 
  if ($('#statistic_chart').length > 0) {
    var options = {
      chart: {
        type: 'line',
        height: 345,
      },
      series: [{
        name: 'Avg. Exam Score',
        data: [0, 32, 40, 50, 60, 52, 50,44,40,60,75,70] // Sample data
      }, {
        name: 'Avg. Attendance',
        data: [0,35,43,34,30,28,25,50,60,75,77,80] // Sample data
      }],
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return val + "%";
          }
        },
        shared: true,
        intersect: false,
        custom: function({series, seriesIndex, dataPointIndex, w}) {
          return `<div class="apexcharts-tooltip">${w.globals.labels[dataPointIndex]}<br>Exam Score: <span style="color: #1E90FF;">${series[0][dataPointIndex]}%</span><br>Attendance: <span style="color: #00BFFF;">${series[1][dataPointIndex]}%</span></div>`;
        }
      },
      dataLabels: {
        enabled: false
      },
      grid: {
        yaxis: {
          lines: {
            show: true
          }
        },
      }, 
      yaxis: {
        labels: {
          offsetX: -15
        },
      },
      grid: {
        padding: {
          left: -8,
        },
      },
      markers: {
        size: 0,
        colors: ['#1E90FF', '#00BFFF'],
        strokeColors: '#fff',
        strokeWidth: 1,
        hover: {
          size: 7
        }
      },
      colors: ['#3D5EE1', '#6FCCD8'], // Color for the lines
      legend: {
        position: 'top',
        horizontalAlign: 'left'
      }
    }
    var chart = new ApexCharts(document.querySelector("#statistic_chart"), options);
    chart.render();
  }

  if ($('#attendance_chart2').length > 0) {
    var donutChart = {
        chart: {
            height: 290,
            type: 'donut',
            toolbar: {
              show: false,
            }
        },
       plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%'
            },
        },
      dataLabels: {
            enabled: false
        },
      
        series: [60,5,15,20],
      labels: [
            'Present',
            'Late',
            'Half Day',
            'Absent'
        ],
        colors:['#1ABE17','#1170E4','#E9EDF4','#E82646'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'left'
                }
            }
        }],
      legend: {
            position: 'left',
      }
    }
    
    var donut = new ApexCharts(
        document.querySelector("#attendance_chart2"),
        donutChart
    );
    
    donut.render();
  }

// Total Earning
 if($('#total-earning').length > 0 ){
  var sLineArea = {
      chart: {
          height: 90,
          type: 'area',
          toolbar: {
            show: false,
          },
          sparkline: {
            enabled: true
          }
      },
      colors: ['#3D5EE1'],
      dataLabels: {
          enabled: false
      },
      stroke: {
          curve: 'straight'
      },
      series: [{
          name: 'Earnings',
          data: [50, 60, 40, 50, 45, 55, 50]
      }]
  }
  
  var chart = new ApexCharts(
      document.querySelector("#total-earning"),
      sLineArea
  );
  
  chart.render();
  }

  // Total Expenses
 if($('#total-expenses').length > 0 ){
  var sLineArea = {
      chart: {
          height: 90,
          type: 'area',
          toolbar: {
            show: false,
          },
          sparkline: {
            enabled: true
          }
      },
      colors: ['#E82646'],
      dataLabels: {
          enabled: false
      },
      stroke: {
          curve: 'straight'
      },
      series: [{
          name: 'Earnings',
          data: [40, 20, 60, 55, 50, 55, 40]
      }]
  }
  
  var chart = new ApexCharts(
      document.querySelector("#total-expenses"),
      sLineArea
  );
  
  chart.render();
  }
  
});


