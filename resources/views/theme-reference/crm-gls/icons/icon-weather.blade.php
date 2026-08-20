<?php $page = 'icon-weather'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper cardhead">
        <div class="content container-fluid">

            @component('components.breadcrumb')
                @slot('title')
                    Weather Icon
                @endslot
            @endcomponent

            <div class="row">

                <!-- Chart -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Weather Icon</div>
                        </div>
                        <div class="card-body">
                            <div class="icons-items">
                                <ul class="icons-list">
                                    <li><i class="wi wi-day-cloudy-high" data-bs-toggle="tooltip"
                                            title="wi wi-day-cloudy-high"></i></li>
                                    <li><i class="wi wi-moonrise" data-bs-toggle="tooltip" title="wi wi-moonrise"></i></li>
                                    <li><i class="wi wi-na" data-bs-toggle="tooltip" title="wi wi-na"></i></li>
                                    <li><i class="wi wi-volcano" data-bs-toggle="tooltip" title="wi wi-volcano"></i></li>
                                    <li><i class="wi wi-day-light-wind" data-bs-toggle="tooltip"
                                            title="wi wi-day-light-wind"></i></li>
                                    <li><i class="wi wi-moonset" data-bs-toggle="tooltip" title="wi wi-moonset"></i></li>
                                    <li><i class="wi wi-flood" data-bs-toggle="tooltip" title="wi wi-flood"></i></li>
                                    <li><i class="wi wi-train" data-bs-toggle="tooltip" title="wi wi-train"></i></li>
                                    <li><i class="wi wi-day-sleet" data-bs-toggle="tooltip" title="wi wi-day-sleet"></i>
                                    </li>
                                    <li><i class="wi wi-night-sleet" data-bs-toggle="tooltip" title="wi wi-night-sleet"></i>
                                    </li>
                                    <li><i class="wi wi-sandstorm" data-bs-toggle="tooltip" title="wi wi-sandstorm"></i>
                                    </li>
                                    <li><i class="wi wi-small-craft-advisory" data-bs-toggle="tooltip"
                                            title="wi wi-small-craft-advisory"></i></li>
                                    <li><i class="wi wi-day-haze" data-bs-toggle="tooltip" title="wi wi-day-haze"></i></li>
                                    <li><i class="wi wi-night-alt-sleet" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-sleet"></i></li>
                                    <li><i class="wi wi-tsunami" data-bs-toggle="tooltip" title="wi wi-tsunami"></i></li>
                                    <li><i class="wi wi-gale-warning" data-bs-toggle="tooltip"
                                            title="wi wi-gale-warning"></i></li>
                                    <li><i class="wi wi-night-cloudy-high" data-bs-toggle="tooltip"
                                            title="wi wi-night-cloudy-high"></i></li>
                                    <li><i class="wi wi-raindrop" data-bs-toggle="tooltip" title="wi wi-raindrop"></i></li>
                                    <li><i class="wi wi-earthquake" data-bs-toggle="tooltip" title="wi wi-earthquake"></i>
                                    </li>
                                    <li><i class="wi wi-storm-warning" data-bs-toggle="tooltip"
                                            title="wi wi-storm-warning"></i></li>
                                    <li><i class="wi wi-night-alt-partly-cloudy" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-partly-cloudy"></i></li>
                                    <li><i class="wi wi-barometer" data-bs-toggle="tooltip" title="wi wi-barometer"></i>
                                    </li>
                                    <li><i class="wi wi-fire" data-bs-toggle="tooltip" title="wi wi-fire"></i></li>
                                    <li><i class="wi wi-hurricane-warning" data-bs-toggle="tooltip"
                                            title="wi wi-hurricane-warning"></i></li>
                                    <li><i class="wi wi-sleet" data-bs-toggle="tooltip" title="wi wi-sleet"></i></li>
                                    <li><i class="wi wi-humidity" data-bs-toggle="tooltip" title="wi wi-humidity"></i></li>
                                    <li><i class="wi wi-day-sunny" data-bs-toggle="tooltip" title="wi wi-day-sunny"></i>
                                    </li>
                                    <li><i class="wi wi-day-cloudy" data-bs-toggle="tooltip" title="wi wi-day-cloudy"></i>
                                    </li>
                                    <li><i class="wi wi-day-cloudy-gusts" data-bs-toggle="tooltip"
                                            title="wi wi-day-cloudy-gusts"></i></li>
                                    <li><i class="wi wi-day-cloudy-windy" data-bs-toggle="tooltip"
                                            title="wi wi-day-cloudy-windy"></i></li>
                                    <li><i class="wi wi-day-fog" data-bs-toggle="tooltip" title="wi wi-day-fog"></i></li>
                                    <li><i class="wi wi-day-hail" data-bs-toggle="tooltip" title="wi wi-day-hail"></i>
                                    </li>
                                    <li><i class="wi wi-day-haze" data-bs-toggle="tooltip" title="wi wi-day-haze"></i>
                                    </li>
                                    <li><i class="wi wi-day-lightning" data-bs-toggle="tooltip"
                                            title="wi wi-day-lightning"></i></li>
                                    <li><i class="wi wi-day-rain" data-bs-toggle="tooltip" title="wi wi-day-rain"></i>
                                    </li>
                                    <li><i class="wi wi-day-rain-mix" data-bs-toggle="tooltip"
                                            title="wi wi-day-rain-mix"></i></li>
                                    <li><i class="wi wi-day-rain-wind" data-bs-toggle="tooltip"
                                            title="wi wi-day-rain-wind"></i></li>
                                    <li><i class="wi wi-day-showers" data-bs-toggle="tooltip"
                                            title="wi wi-day-showers"></i></li>
                                    <li><i class="wi wi-day-sleet" data-bs-toggle="tooltip" title="wi wi-day-sleet"></i>
                                    </li>
                                    <li><i class="wi wi-day-sleet-storm" data-bs-toggle="tooltip"
                                            title="wi wi-day-sleet-storm"></i></li>
                                    <li><i class="wi wi-day-snow" data-bs-toggle="tooltip" title="wi wi-day-snow"></i>
                                    </li>
                                    <li><i class="wi wi-day-snow-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-day-snow-thunderstorm"></i></li>
                                    <li><i class="wi wi-day-snow-wind" data-bs-toggle="tooltip"
                                            title="wi wi-day-snow-wind"></i></li>
                                    <li><i class="wi wi-day-sprinkle" data-bs-toggle="tooltip"
                                            title="wi wi-day-sprinkle"></i></li>
                                    <li><i class="wi wi-day-storm-showers" data-bs-toggle="tooltip"
                                            title="wi wi-day-storm-showers"></i></li>
                                    <li><i class="wi wi-day-sunny-overcast" data-bs-toggle="tooltip"
                                            title="wi wi-day-sunny-overcast"></i></li>
                                    <li><i class="wi wi-day-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-day-thunderstorm"></i></li>
                                    <li><i class="wi wi-day-windy" data-bs-toggle="tooltip" title="wi wi-day-windy"></i>
                                    </li>
                                    <li><i class="wi wi-solar-eclipse" data-bs-toggle="tooltip"
                                            title="wi wi-solar-eclipse"></i></li>
                                    <li><i class="wi wi-hot" data-bs-toggle="tooltip" title="wi wi-hot"></i></li>
                                    <li><i class="wi wi-day-cloudy-high" data-bs-toggle="tooltip"
                                            title="wi wi-day-cloudy-high"></i></li>
                                    <li><i class="wi wi-day-light-wind" data-bs-toggle="tooltip"
                                            title="wi wi-day-light-wind"></i></li>
                                    <li><i class="wi wi-night-clear" data-bs-toggle="tooltip"
                                            title="wi wi-night-clear"></i></li>
                                    <li><i class="wi wi-night-alt-cloudy" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-cloudy"></i></li>
                                    <li><i class="wi wi-night-alt-cloudy-gusts" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-cloudy-gusts"></i></li>
                                    <li><i class="wi wi-night-alt-cloudy-windy" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-cloudy-windy"></i></li>
                                    <li><i class="wi wi-night-alt-hail" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-hail"></i></li>
                                    <li><i class="wi wi-night-alt-lightning" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-lightning"></i></li>
                                    <li><i class="wi wi-night-alt-rain" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-rain"></i></li>
                                    <li><i class="wi wi-night-alt-rain-mix" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-rain-mix"></i></li>
                                    <li><i class="wi wi-night-alt-rain-wind" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-rain-wind"></i></li>
                                    <li><i class="wi wi-night-alt-showers" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-showers"></i></li>
                                    <li><i class="wi wi-night-alt-sleet" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-sleet"></i></li>
                                    <li><i class="wi wi-night-alt-sleet-storm" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-sleet-storm"></i></li>
                                    <li><i class="wi wi-night-alt-snow" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-snow"></i></li>
                                    <li><i class="wi wi-night-alt-snow-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-snow-thunderstorm"></i></li>
                                    <li><i class="wi wi-night-alt-snow-wind" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-snow-wind"></i></li>
                                    <li><i class="wi wi-night-alt-sprinkle" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-sprinkle"></i></li>
                                    <li><i class="wi wi-night-alt-storm-showers" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-storm-showers"></i></li>
                                    <li><i class="wi wi-night-alt-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-thunderstorm"></i></li>
                                    <li><i class="wi wi-night-cloudy" data-bs-toggle="tooltip"
                                            title="wi wi-night-cloudy"></i></li>
                                    <li><i class="wi wi-night-cloudy-gusts" data-bs-toggle="tooltip"
                                            title="wi wi-night-cloudy-gusts"></i></li>
                                    <li><i class="wi wi-night-cloudy-windy" data-bs-toggle="tooltip"
                                            title="wi wi-night-cloudy-windy"></i></li>
                                    <li><i class="wi wi-night-fog" data-bs-toggle="tooltip" title="wi wi-night-fog"></i>
                                    </li>
                                    <li><i class="wi wi-night-hail" data-bs-toggle="tooltip"
                                            title="wi wi-night-hail"></i></li>
                                    <li><i class="wi wi-night-lightning" data-bs-toggle="tooltip"
                                            title="wi wi-night-lightning"></i></li>
                                    <li><i class="wi wi-night-partly-cloudy" data-bs-toggle="tooltip"
                                            title="wi wi-night-partly-cloudy"></i></li>
                                    <li><i class="wi wi-night-rain" data-bs-toggle="tooltip"
                                            title="wi wi-night-rain"></i></li>
                                    <li><i class="wi wi-night-rain-mix" data-bs-toggle="tooltip"
                                            title="wi wi-night-rain-mix"></i></li>
                                    <li><i class="wi wi-night-rain-wind" data-bs-toggle="tooltip"
                                            title="wi wi-night-rain-wind"></i></li>
                                    <li><i class="wi wi-night-showers" data-bs-toggle="tooltip"
                                            title="wi wi-night-showers"></i></li>
                                    <li><i class="wi wi-night-sleet" data-bs-toggle="tooltip"
                                            title="wi wi-night-sleet"></i></li>
                                    <li><i class="wi wi-night-sleet-storm" data-bs-toggle="tooltip"
                                            title="wi wi-night-sleet-storm"></i></li>
                                    <li><i class="wi wi-night-snow" data-bs-toggle="tooltip"
                                            title="wi wi-night-snow"></i></li>
                                    <li><i class="wi wi-night-snow-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-night-snow-thunderstorm"></i></li>
                                    <li><i class="wi wi-night-snow-wind" data-bs-toggle="tooltip"
                                            title="wi wi-night-snow-wind"></i></li>
                                    <li><i class="wi wi-night-sprinkle" data-bs-toggle="tooltip"
                                            title="wi wi-night-sprinkle"></i></li>
                                    <li><i class="wi wi-night-storm-showers" data-bs-toggle="tooltip"
                                            title="wi wi-night-storm-showers"></i></li>
                                    <li><i class="wi wi-night-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-night-thunderstorm"></i></li>
                                    <li><i class="wi wi-lunar-eclipse" data-bs-toggle="tooltip"
                                            title="wi wi-lunar-eclipse"></i></li>
                                    <li><i class="wi wi-stars" data-bs-toggle="tooltip" title="wi wi-stars"></i></li>
                                    <li><i class="wi wi-storm-showers" data-bs-toggle="tooltip"
                                            title="wi wi-storm-showers"></i></li>
                                    <li><i class="wi wi-night-alt-cloudy-high" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-cloudy-high"></i></li>
                                    <li><i class="wi wi-night-cloudy-high" data-bs-toggle="tooltip"
                                            title="wi wi-night-cloudy-high"></i></li>
                                    <li><i class="wi wi-night-alt-partly-cloudy" data-bs-toggle="tooltip"
                                            title="wi wi-night-alt-partly-cloudy"></i></li>
                                    <li><i class="wi wi-cloud" data-bs-toggle="tooltip" title="wi wi-cloud"></i></li>
                                    <li><i class="wi wi-cloudy" data-bs-toggle="tooltip" title="wi wi-cloudy"></i></li>
                                    <li><i class="wi wi-cloudy-gusts" data-bs-toggle="tooltip"
                                            title="wi wi-cloudy-gusts"></i></li>
                                    <li><i class="wi wi-cloudy-windy" data-bs-toggle="tooltip"
                                            title="wi wi-cloudy-windy"></i></li>
                                    <li><i class="wi wi-fog" data-bs-toggle="tooltip" title="wi wi-fog"></i></li>
                                    <li><i class="wi wi-hail" data-bs-toggle="tooltip" title="wi wi-hail"></i></li>
                                    <li><i class="wi wi-rain" data-bs-toggle="tooltip" title="wi wi-rain"></i></li>
                                    <li><i class="wi wi-rain-mix" data-bs-toggle="tooltip" title="wi wi-rain-mix"></i>
                                    </li>
                                    <li><i class="wi wi-rain-wind" data-bs-toggle="tooltip" title="wi wi-rain-wind"></i>
                                    </li>
                                    <li><i class="wi wi-showers" data-bs-toggle="tooltip" title="wi wi-showers"></i></li>
                                    <li><i class="wi wi-sleet" data-bs-toggle="tooltip" title="wi wi-sleet"></i></li>
                                    <li><i class="wi wi-snow" data-bs-toggle="tooltip" title="wi wi-snow"></i></li>
                                    <li><i class="wi wi-sprinkle" data-bs-toggle="tooltip" title="wi wi-sprinkle"></i>
                                    </li>
                                    <li><i class="wi wi-storm-showers" data-bs-toggle="tooltip"
                                            title="wi wi-storm-showers"></i></li>
                                    <li><i class="wi wi-thunderstorm" data-bs-toggle="tooltip"
                                            title="wi wi-thunderstorm"></i></li>
                                    <li><i class="wi wi-snow-wind" data-bs-toggle="tooltip" title="wi wi-snow-wind"></i>
                                    </li>
                                    <li><i class="wi wi-snow" data-bs-toggle="tooltip" title="wi wi-snow"></i></li>
                                    <li><i class="wi wi-smog" data-bs-toggle="tooltip" title="wi wi-smog"></i></li>
                                    <li><i class="wi wi-smoke" data-bs-toggle="tooltip" title="wi wi-smoke"></i></li>
                                    <li><i class="wi wi-lightning" data-bs-toggle="tooltip" title="wi wi-lightning"></i>
                                    </li>
                                    <li><i class="wi wi-raindrops" data-bs-toggle="tooltip" title="wi wi-raindrops"></i>
                                    </li>
                                    <li><i class="wi wi-raindrop" data-bs-toggle="tooltip" title="wi wi-raindrop"></i>
                                    </li>
                                    <li><i class="wi wi-snowflake-cold" data-bs-toggle="tooltip"
                                            title="wi wi-snowflake-cold"></i></li>
                                    <li><i class="wi wi-windy" data-bs-toggle="tooltip" title="wi wi-windy"></i></li>
                                    <li><i class="wi wi-strong-wind" data-bs-toggle="tooltip"
                                            title="wi wi-strong-wind"></i></li>
                                    <li><i class="wi wi-sandstorm" data-bs-toggle="tooltip" title="wi wi-sandstorm"></i>
                                    </li>
                                    <li><i class="wi wi-earthquake" data-bs-toggle="tooltip"
                                            title="wi wi-earthquake"></i></li>
                                    <li><i class="wi wi-fire" data-bs-toggle="tooltip" title="wi wi-fire"></i></li>
                                    <li><i class="wi wi-flood" data-bs-toggle="tooltip" title="wi wi-flood"></i></li>
                                    <li><i class="wi wi-meteor" data-bs-toggle="tooltip" title="wi wi-meteor"></i></li>
                                    <li><i class="wi wi-tsunami" data-bs-toggle="tooltip" title="wi wi-tsunami"></i></li>
                                    <li><i class="wi wi-volcano" data-bs-toggle="tooltip" title="wi wi-volcano"></i></li>
                                    <li><i class="wi wi-hurricane" data-bs-toggle="tooltip" title="wi wi-hurricane"></i>
                                    </li>
                                    <li><i class="wi wi-tornado" data-bs-toggle="tooltip" title="wi wi-tornado"></i></li>
                                    <li><i class="wi wi-small-craft-advisory" data-bs-toggle="tooltip"
                                            title="wi wi-small-craft-advisory"></i></li>
                                    <li><i class="wi wi-gale-warning" data-bs-toggle="tooltip"
                                            title="wi wi-gale-warning"></i></li>
                                    <li><i class="wi wi-storm-warning" data-bs-toggle="tooltip"
                                            title="wi wi-storm-warning"></i></li>
                                    <li><i class="wi wi-hurricane-warning" data-bs-toggle="tooltip"
                                            title="wi wi-hurricane-warning"></i></li>
                                    <li><i class="wi wi-wind-direction" data-bs-toggle="tooltip"
                                            title="wi wi-wind-direction"></i></li>
                                    <li><i class="wi wi-alien" data-bs-toggle="tooltip" title="wi wi-alien"></i></li>
                                    <li><i class="wi wi-celsius" data-bs-toggle="tooltip" title="wi wi-celsius"></i></li>
                                    <li><i class="wi wi-fahrenheit" data-bs-toggle="tooltip"
                                            title="wi wi-fahrenheit"></i></li>
                                    <li><i class="wi wi-degrees" data-bs-toggle="tooltip" title="wi wi-degrees"></i></li>
                                    <li><i class="wi wi-thermometer" data-bs-toggle="tooltip"
                                            title="wi wi-thermometer"></i></li>
                                    <li><i class="wi wi-thermometer-exterior" data-bs-toggle="tooltip"
                                            title="wi wi-thermometer-exterior"></i></li>
                                    <li><i class="wi wi-thermometer-internal" data-bs-toggle="tooltip"
                                            title="wi wi-thermometer-internal"></i></li>
                                    <li><i class="wi wi-cloud-down" data-bs-toggle="tooltip"
                                            title="wi wi-cloud-down"></i></li>
                                    <li><i class="wi wi-cloud-up" data-bs-toggle="tooltip" title="wi wi-cloud-up"></i>
                                    </li>
                                    <li><i class="wi wi-cloud-refresh" data-bs-toggle="tooltip"
                                            title="wi wi-cloud-refresh"></i></li>
                                    <li><i class="wi wi-horizon" data-bs-toggle="tooltip" title="wi wi-horizon"></i></li>
                                    <li><i class="wi wi-horizon-alt" data-bs-toggle="tooltip"
                                            title="wi wi-horizon-alt"></i></li>
                                    <li><i class="wi wi-sunrise" data-bs-toggle="tooltip" title="wi wi-sunrise"></i></li>
                                    <li><i class="wi wi-sunset" data-bs-toggle="tooltip" title="wi wi-sunset"></i></li>
                                    <li><i class="wi wi-moonrise" data-bs-toggle="tooltip" title="wi wi-moonrise"></i>
                                    </li>
                                    <li><i class="wi wi-moonset" data-bs-toggle="tooltip" title="wi wi-moonset"></i></li>
                                    <li><i class="wi wi-refresh" data-bs-toggle="tooltip"
                                            title="typcn typcn-rss-outline"></i></li>
                                    <li><i class="wi wi-refresh-alt" data-bs-toggle="tooltip"
                                            title="wi wi-refresh-alt"></i></li>
                                    <li><i class="wi wi-umbrella" data-bs-toggle="tooltip" title="wi wi-umbrella"></i>
                                    </li>
                                    <li><i class="wi wi-barometer" data-bs-toggle="tooltip" title="wi wi-barometer"></i>
                                    </li>
                                    <li><i class="wi wi-humidity" data-bs-toggle="tooltip" title="wi wi-humidity"></i>
                                    </li>
                                    <li><i class="wi wi-na" data-bs-toggle="tooltip" title="wi wi-na"></i></li>
                                    <li><i class="wi wi-train" data-bs-toggle="tooltip" title="wi wi-train"></i></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Chart -->

            </div>

        </div>
    </div>
@endsection
