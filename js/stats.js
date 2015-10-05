jQuery(document).ready(function($) {

	var impression_color           	= "rgb(74, 174, 238)";
	var impression_color_light 		= "rgba(74, 174, 238, 0.2)";
	var conversion_color       		= "rgb(241, 89, 40)";
	var conversion_color_light 		= "rgba(241, 89, 40, 0.2)";

	// Load stats via AJAX

	$.post(ajaxurl, {
			'action': 'nnr_stats_tracker_load_v1',
			'table_name' : nnr_stats_tracker_data.table_name,
			'data_table_name' : nnr_stats_tracker_data.data_table_name,
			'text_domain' : nnr_stats_tracker_data.text_domain,
			'prefix' : nnr_stats_tracker_data.prefix,
			'stats_page' : nnr_stats_tracker_data.stats_page,
			'start_date' : getQuerystring('start_date'),
			'end_date' : getQuerystring('end_date'),
			'name' : getQuerystring('data_name'),
			'data_id' : getQuerystring('data_id', false)}, function(response) {

		response = jQuery.parseJSON(response);

		$("#nnr-loading-stats").remove();
		$(response.stats_content).insertAfter("#nnr-before-table");

		if ( $("#nnr-line-graph").length != 0 ) {

			// All data or single data stats

			var dates = [];
			var data = [];
			var impressions = [];
			var conversions = [];

			$.each(response.data_stats, function(key, value){

				$.each(value, function(key_1, value_1){

					if (key_1 == 'name') {
						data.push(value_1);
					}

					if (key_1 == 'date') {
						dates.push(value_1);
					}

					if (key_1 == 'impressions') {
						impressions.push(value_1);
					}

					if (key_1 == 'conversions') {
						conversions.push(value_1);
					}
				});
			});

			if (getQuerystring('data_id') != '') {
				var labels = dates;
			} else {
				var labels = data;
			}


			// Line Graph

			var lineChartData = {
				labels : labels,
				datasets : [
					{
						label: "Impressions",
						fillColor : impression_color_light,
						strokeColor : impression_color,
						pointColor : impression_color,
						pointStrokeColor : "#fff",
						pointHighlightFill : "#fff",
						pointHighlightStroke : impression_color,
						data : impressions
					},
					{
						label: "Conversions",
						fillColor : conversion_color_light,
						strokeColor : conversion_color,
						pointColor : conversion_color,
						pointStrokeColor : "#fff",
						pointHighlightFill : "#fff",
						pointHighlightStroke : conversion_color,
						data : conversions
					}
				]

			}

			// Show No stats

			if (impressions.length == 0) {

				var ctx = document.getElementById("nnr-line-graph").getContext("2d");
				window.myLine = new Chart(ctx).Line(get_default_data(), {
					responsive: true,
				});

				$('#nnr-line-graph').css('opacity', '0.5');
				$('<h3 style="width: 70%;text-align: center;position: absolute;margin-top: 60px;">Example Data</h3><h3 style="width: 70%;text-align: center;position: absolute;margin-top: 100px;">No Stats Yet</h3>').insertBefore('#nnr-line-graph');
			}

			// Show Line graph if there are multiple stats

			else if (impressions.length > 1 && getQuerystring('data_id') != '') {

				var ctx = document.getElementById("nnr-line-graph").getContext("2d");
				window.myLine = new Chart(ctx).Line(lineChartData, {
					responsive: true,
					pointHitDetectionRadius : 2,
				});

			}

			// Show Bar graph if there is only one day of stats  or on all Firebar stats page

			else {

				var ctx = document.getElementById("nnr-line-graph").getContext("2d");
				window.myLine = new Chart(ctx).Bar(lineChartData, {
					responsive: true,
				});
			}

		}

		if ( $("#nnr-pie-chart").length != 0 ) {

			var stats = [];
			var pieData = [];
			var colors = get_pie_chart_colors();
			var colors_length = colors.length;
			var counter = 0;
			var check = false;

			$.each(response.data_stats, function(key, value){

				if ( value.conversions > 0 ) {
					stats.push(value);
				}

			});

			$.each(stats, function(key, value){

				if ( value.conversions > 0 ) {

					check = true;

					var color_index = counter%colors_length;

					pieData.push({
						value: parseInt(value.conversions),
						color: colors[color_index].color,
						highlight: colors[color_index].highlight,
						label: value.name,
					});

				}

				counter++;

			});

			if ( !check ) {

				// Pie Chart

				var ctx = document.getElementById("nnr-pie-chart").getContext("2d");
				window.myPie = new Chart(ctx).Pie(get_default_data_pie(), {
					responsive: true,
					animationSteps : 50,
				});

				$('#nnr-pie-chart').css('opacity', '0.5');
				$('<h3 style="width: 100%;text-align: center;position: absolute;padding-top:20%;">Example Data</h3><h3 style="width: 100%;text-align: center;position: absolute;padding-top:25%;">No Stats Yet</h3>').insertBefore('#nnr-pie-chart');
			}

			// Show the data

			else {

				var ctx = document.getElementById("nnr-pie-chart").getContext("2d");
				window.myPie = new Chart(ctx).Pie(pieData, {
					responsive: true,
					animationSteps : 50,
				});

			}

		}

		$($.bootstrapSortable);

	});

	// Date Pickers

	if ($('#nnr-start-datepicker').length != 0 &&
		$('#nnr-end-datepicker').length != 0) {

		$('#nnr-start-datepicker').datetimepicker({
	        format: 'MM/DD/YYYY'
	    });

		$('#nnr-end-datepicker').datetimepicker({
	        format: 'MM/DD/YYYY'
	    });

		$('#nnr-end-datepicker')
			.data("DateTimePicker")
			.minDate( $('#nnr-start-datepicker').data("DateTimePicker").date() );

		$('#nnr-start-datepicker').on("dp.change",function (e) {
	       $('#nnr-end-datepicker').data("DateTimePicker").minDate(e.date);
	    });

	    $('#nnr-end-datepicker').on("dp.change",function (e) {
	       $('#nnr-start-datepicker').data("DateTimePicker").maxDate(e.date);
	    });

	}

	/**
	 * Get default data
	 *
	 * @access public
	 * @return void
	 */
	function get_default_data() {
		return {
			labels : ['1-1-1970','1-2-1970','1-3-1970','1-4-1970','1-5-1970','1-6-1970','1-7-1970','1-8-1970','1-9-1970'],
			datasets : [
				{
					label: "Impressions",
					fillColor : impression_color_light,
					strokeColor : impression_color,
					pointColor : impression_color,
					pointStrokeColor : "#fff",
					pointHighlightFill : "#fff",
					pointHighlightStroke : impression_color,
					data : ['25','45','43','18','26','34','21','20','29']
				},
				{
					label: "Conversions",
					fillColor : conversion_color_light,
					strokeColor : conversion_color,
					pointColor : conversion_color,
					pointStrokeColor : "#fff",
					pointHighlightFill : "#fff",
					pointHighlightStroke : conversion_color,
					data : ['12','3','8','6','11','6','3','5','7']
				}
			]

		};
	}

	/**
	 * Get default data for pie charts
	 *
	 * @access public
	 * @return void
	 */
	function get_default_data_pie() {
		return [
			{
		        value: 300,
		        color:"#F7464A",
		        highlight: "#FF5A5E",
		        label: "Optin 1"
		    },
		    {
		        value: 50,
		        color: "#46BFBD",
		        highlight: "#5AD3D1",
		        label: "Optin 2"
		    },
		    {
		        value: 100,
		        color: "#FDB45C",
		        highlight: "#FFC870",
		        label: "Optin 3"
		    }
	    ];
	}

	/**
	 * Get all the pic chart colors
	 *
	 * @access public
	 * @return void
	 */
	function get_pie_chart_colors() {
		return [
			{
				color: '#00A0B0',
				highlight: '#7fcfd7',
			},
			{
				color: '#EDC951',
				highlight: '#f6e4a8',
			},
			{
				color: '#EB6841',
				highlight: '#f5b3a0',
			},
			{
				color: '#4F372D',
				highlight: '#a79b96',
			},
			{
				color: '#CC2A36',
				highlight: '#e5949a',
			},
		];
	}

    /**
     * Gets a query parameter
     *
     * @access public
     * @param mixed key
     * @param mixed default_
     * @return void
     */
    function getQuerystring(key, default_) {
	  if (default_==null) default_="";
	  key = key.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	  var regex = new RegExp("[\\?&]"+key+"=([^&#]*)");
	  var qs = regex.exec(window.location.href);
	  if(qs == null)
	    return default_;
	  else
	    return qs[1];
	}
});