<?php $page = 'form-checkbox-radios'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper cardhead">
        <div class="content">

            @component('components.breadcrumb')
                @slot('title')
                    Checks & Radios
                @endslot
            @endcomponent

            <div class="row">

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Checkbox</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                <label class="form-check-label" for="flexCheckDefault">
                                    Default checkbox
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked"
                                    checked="">
                                <label class="form-check-label" for="flexCheckChecked">
                                    Checked checkbox
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Disabled</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDisabled"
                                    disabled="">
                                <label class="form-check-label" for="flexCheckDisabled">
                                    Disabled checkbox
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckCheckedDisabled"
                                    checked="" disabled="">
                                <label class="form-check-label" for="flexCheckCheckedDisabled">
                                    Disabled checked checkbox
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Radios</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="flexRadioDefault"
                                    id="flexRadioDefault1">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    Default radio
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="flexRadioDefault"
                                    id="flexRadioDefault2" checked="">
                                <label class="form-check-label" for="flexRadioDefault2">
                                    Default checked radio
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Radios</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="flexRadioDisabled"
                                    id="flexRadioDisabled" disabled="">
                                <label class="form-check-label" for="flexRadioDisabled">
                                    Disabled radio
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="flexRadioDisabled"
                                    id="flexRadioCheckedDisabled" checked="" disabled="">
                                <label class="form-check-label" for="flexRadioCheckedDisabled">
                                    Disabled checked radio
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Default (stacked)</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
                                <label class="form-check-label" for="defaultCheck1">
                                    Default checkbox
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="defaultCheck2"
                                    disabled="">
                                <label class="form-check-label" for="defaultCheck2">
                                    Disabled checkbox
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios1"
                                    value="option1" checked="">
                                <label class="form-check-label" for="exampleRadios1">
                                    Default radio
                                </label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="exampleRadios" id="exampleRadios3"
                                    value="option3" disabled="">
                                <label class="form-check-label" for="exampleRadios3">
                                    Disabled radio
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Switches</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for="flexSwitchCheckDefault">Default switch
                                    checkbox input</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="flexSwitchCheckChecked" checked="">
                                <label class="form-check-label" for="flexSwitchCheckChecked">Checked switch
                                    checkbox input</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="flexSwitchCheckDisabled" disabled="">
                                <label class="form-check-label" for="flexSwitchCheckDisabled">Disabled
                                    switch
                                    checkbox input</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="flexSwitchCheckCheckedDisabled" checked="" disabled="">
                                <label class="form-check-label" for="flexSwitchCheckCheckedDisabled">Disabled
                                    checked switch checkbox input</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Checkbox Sizes
                            </div>

                        </div>
                        <div class="card-body d-sm-flex align-items-center justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="checkebox-sm"
                                    checked="">
                                <label class="form-check-label" for="checkebox-sm">
                                    Default
                                </label>
                            </div>
                            <div class="form-check form-check-md d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" value="" id="checkebox-md"
                                    checked="">
                                <label class="form-check-label" for="checkebox-md">
                                    Medium
                                </label>
                            </div>
                            <div class="form-check form-check-lg d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" value="" id="checkebox-lg"
                                    checked="">
                                <label class="form-check-label" for="checkebox-lg">
                                    Large
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Radio Sizes
                            </div>

                        </div>
                        <div class="card-body d-sm-flex align-items-center justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="Radio" id="Radio-sm">
                                <label class="form-check-label" for="Radio-sm">
                                    Default
                                </label>
                            </div>
                            <div class="form-check form-check-md">
                                <input class="form-check-input" type="radio" name="Radio" id="Radio-md">
                                <label class="form-check-label" for="Radio-md">
                                    Medium
                                </label>
                            </div>
                            <div class="form-check form-check-lg">
                                <input class="form-check-input" type="radio" name="Radio" id="Radio-lg"
                                    checked="">
                                <label class="form-check-label" for="Radio-lg">
                                    Large
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-xxl-4 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="card">
                        <div class="card-header justify-content-between">


                            <div class="card-title">
                                Switch Sizes
                            </div>
                        </div>
                        <div class="card-body d-sm-flex align-item-center justify-content-between">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                <label class="form-check-label" for="switch-sm">Default</label>
                            </div>
                            <div class="form-check form-check-md form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switch-md">
                                <label class="form-check-label" for="switch-md">Medium</label>
                            </div>
                            <div class="form-check form-check-lg form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switch-lg">
                                <label class="form-check-label" for="switch-lg">Large</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Inline</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="option1">
                                <label class="form-check-label" for="inlineCheckbox1">1</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="option2">
                                <label class="form-check-label" for="inlineCheckbox2">2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox3" value="option3"
                                    disabled="">
                                <label class="form-check-label" for="inlineCheckbox3">3 (disabled)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                    id="inlineRadio1" value="option1">
                                <label class="form-check-label" for="inlineRadio1">1</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                    id="inlineRadio2" value="option2">
                                <label class="form-check-label" for="inlineRadio2">2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                    id="inlineRadio3" value="option3" disabled="">
                                <label class="form-check-label" for="inlineRadio3">3 (disabled)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <h5 class="card-title">Without labels</h5>
                        </div>
                        <div class="card-body">
                            <span class="me-3">
                                <input class="form-check-input" type="checkbox" id="checkboxNoLabel" value=""
                                    aria-label="...">
                            </span>
                            <span>
                                <input class="form-check-input" type="radio" name="radioNoLabel" id="radioNoLabel1"
                                    value="" aria-label="...">
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
