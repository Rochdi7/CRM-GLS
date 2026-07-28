$(function() {
	'use strict';
	
	// Helper function to safely get canvas context
	function getChartContext(canvasId) {
		const canvas = document.getElementById(canvasId);
		return canvas ? canvas.getContext('2d') : null;
	}
	
	// Chart 1 - Bar Chart
	var ctx1 = getChartContext('chartBar1');
	if (ctx1) {
		new Chart(ctx1, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [24, 10, 32, 24, 26, 20],
					backgroundColor: '#664dc9'
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							},
							max: 80
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 2 - Transparency Bar Chart
	var ctx2 = getChartContext('chartBar2');
	if (ctx2) {
		new Chart(ctx2, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [14, 12, 34, 25, 24, 20],
					backgroundColor: '#44c4fa'
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							},
							max: 80
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 3 - Gradient Bar Chart
	var ctx3 = getChartContext('chartBar3');
	if (ctx3) {
		var gradient = ctx3.createLinearGradient(0, 0, 0, 250);
		gradient.addColorStop(0, '#44c4fa');
		gradient.addColorStop(1, '#664dc9');
		new Chart(ctx3, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [14, 12, 34, 25, 24, 20],
					backgroundColor: gradient
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							},
							max: 80
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 4 - Horizontal Bar Chart
	var ctx4 = getChartContext('chartBar4');
	if (ctx4) {
		new Chart(ctx4, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [14, 12, 34, 25, 24, 20],
					backgroundColor: ['#664dc9', '#44c4fa', '#38cb89', '#3e80eb', '#ffab00', '#ef4b4b']
				}]
			},
			options: {
				indexAxis: 'y',
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							}
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							},
							max: 80
						}
					}
				}
			}
		});
	}
	
	// Chart 5 - Horizontal Bar Chart Style2
	var ctx5 = getChartContext('chartBar5');
	if (ctx5) {
		new Chart(ctx5, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
				datasets: [{
					data: [14, 12, 34, 25, 24, 20],
					backgroundColor: [ '#664dc9', '#38cb89', '#116e7c', '#ffab00', '#ef4b4b']
				}, {
					data: [22, 30, 25, 30, 20, 40],
					backgroundColor: '#44c4fa'
				}]
			},
			options: {
				indexAxis: 'y',
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 11
							}
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							},
							max: 80
						}
					}
				}
			}
		});
	}
	
	// Chart 6 - Vertical Stacked Bar Chart
	var ctx6 = getChartContext('chartStacked1');
	if (ctx6) {
		new Chart(ctx6, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [14, 12, 34, 25, 24, 20],
					backgroundColor: '#664dc9'
				}, {
					label: 'Profit',
					data: [22, 30, 25, 30, 20, 40],
					backgroundColor: '#44c4fa'
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							},
							max: 80
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 7 - Horizontal Stacked Bar Chart
	var ctx7 = getChartContext('chartStacked2');
	if (ctx7) {
		new Chart(ctx7, {
			type: 'bar',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [14, 12, 34, 25, 24, 20],
					backgroundColor: '#664dc9'
				}, {
					label: 'Profit',
					data: [22, 30, 25, 30, 20, 40],
					backgroundColor: '#44c4fa'
				}]
			},
			options: {
				indexAxis: 'y',
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							}
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							},
							max: 80
						}
					}
				}
			}
		});
	}
	
	// Chart 8 - Line Chart
	var ctx8 = getChartContext('chartLine1');
	if (ctx8) {
		new Chart(ctx8, {
			type: 'line',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [24, 10, 32, 24, 26, 20],
					borderColor: '#664dc9',
					backgroundColor: '#664dc9',
					tension: 0.4
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							},
							max: 80
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 9 - Area Chart
	var ctx9 = getChartContext('chartArea1');
	if (ctx9) {
		var gradient = ctx9.createLinearGradient(0, 0, 0, 250);
		gradient.addColorStop(0, '#664dc9');
		gradient.addColorStop(1, 'rgba(102, 77, 201, 0.1)');
		new Chart(ctx9, {
			type: 'line',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
				datasets: [{
					label: 'Sales',
					data: [24, 10, 32, 24, 26, 20],
					borderColor: '#664dc9',
					backgroundColor: gradient,
					tension: 0.4,
					fill: true
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
						labels: {
							display: false
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							font: {
								size: 10
							},
							max: 80
						}
					},
					x: {
						ticks: {
							beginAtZero: true,
							font: {
								size: 11
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 10 - Donut Chart (now Pie Chart design)
	var ctx10 = getChartContext('chartDonut');
	if (ctx10) {
		new Chart(ctx10, {
			type: 'pie',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
				datasets: [{
					data: [40, 12, 8, 18, 22],
					backgroundColor: ['#664dc9', '#44c4fa', '#38cb89', '#3e80eb', '#ffab00'],
					borderWidth: 2,
					borderColor: '#ffffff',
					spacing: 2
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: true,
						position: 'top',
						labels: {
							usePointStyle: true,
							padding: 20,
							font: {
								size: 12
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 11 - Pie Chart (now Donut Chart design)
	var ctx11 = getChartContext('chartPie');
	if (ctx11) {
		new Chart(ctx11, {
			type: 'doughnut',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
				datasets: [{
					data: [40, 12, 8, 18, 22],
					backgroundColor: ['#664dc9', '#44c4fa', '#38cb89', '#3e80eb', '#ffab00'],
					borderWidth: 2,
					borderColor: '#ffffff',
					cutout: '65%',
					spacing: 2
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: true,
						position: 'top',
						labels: {
							usePointStyle: true,
							padding: 20,
							font: {
								size: 12
							}
						}
					}
				}
			}
		});
	}
	
	// Chart 12 - Scatter Chart
	var ctx12 = getChartContext('chartRadar');
	if (ctx12) {
		new Chart(ctx12, {
			type: 'scatter',
			data: {
				datasets: [{
					label: 'Appointment',
					data: [
						{x: -10, y: 0},
						{x: 0.5, y: 9.9},
						{x: 0.5, y: 5.5},
						{x: 10, y: 5}
					],
					backgroundColor: '#3e80eb',
					borderColor: '#3e80eb',
					pointRadius: 6,
					pointHoverRadius: 8
				}]
			},
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: true,
						position: 'top',
						labels: {
							usePointStyle: true,
							padding: 20,
							font: {
								size: 12
							}
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						max: 10,
						ticks: {
							stepSize: 1,
							font: {
								size: 10
							}
						},
						grid: {
							color: '#f0f0f0'
						}
					},
					x: {
						min: -10,
						max: 10,
						ticks: {
							stepSize: 2,
							font: {
								size: 10
							}
						},
						grid: {
							color: '#f0f0f0'
						}
					}
				}
			}
		});
	}
});