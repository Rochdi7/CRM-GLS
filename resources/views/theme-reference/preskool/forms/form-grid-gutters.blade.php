<?php $page = 'form-grid-gutters'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper cardhead">
        <div class="content">

            @component('components.breadcrumb')
                @slot('title')
                    Grid & Gutters
                @endslot
            @endcomponent

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Form Grid
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" placeholder="First Name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" placeholder="Last Name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Address</label>
                                    <div class="row">
                                        <div class="col-xl-12 mb-3">
                                            <input type="text" class="form-control" placeholder="Street">
                                        </div>
                                        <div class="col-xl-12 mb-3">
                                            <input type="text" class="form-control" placeholder="Landmark">
                                        </div>
                                        <div class="col-xxl-6 col-xl-12 mb-3">
                                            <input type="text" class="form-control" placeholder="City">
                                        </div>
                                        <div class="col-xxl-6 col-xl-12 mb-3">
                                            <select id="inputState1" class="form-select">
                                                <option selected="">State</option>
                                                <option>...</option>
                                            </select>
                                        </div>
                                        <div class="col-xxl-6 col-xl-12 mb-3">
                                            <input type="text" class="form-control" placeholder="Postal/Zip code">
                                        </div>
                                        <div class="col-xxl-6 col-xl-12 mb-3">
                                            <select id="inputCountry" class="form-select">
                                                <option selected="">Country</option>
                                                <option>...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="row">
                                        <div class="col-xl-12 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" placeholder="Email">
                                        </div>
                                        <div class="col-xl-12 mb-3">
                                            <label class="form-label">DOB</label>
                                            <input type="date" class="form-control">
                                        </div>
                                        <div class="col-xl-12 mb-3">
                                            <div class="row">
                                                <label class="form-label mb-1">Maritial Status</label>
                                                <div class="col-xl-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="status-married" required="">
                                                        <label class="form-check-label" for="status-married">
                                                            Married
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-xl-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="status-unmarried" required="">
                                                        <label class="form-check-label" for="status-unmarried">
                                                            Single
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-12">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="number" class="form-control" placeholder="Phone Number">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alternative Contact</label>
                                    <input type="number" class="form-control" placeholder="Phone Number">
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="gridCheck">
                                        <label class="form-check-label" for="gridCheck">
                                            Check me out
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Sign in</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Gutters
                            </div>

                        </div>
                        <div class="card-body">
                            <form class="row g-3 mt-0">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" placeholder="First Name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" placeholder="Last Name">
                                </div>
                                <div class="col-md-6">
                                    <label for="inputEmail4" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="inputEmail4">
                                </div>
                                <div class="col-md-6">
                                    <label for="inputPassword4" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="inputPassword4">
                                </div>
                                <div class="col-12">
                                    <label for="inputAddress" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="inputAddress">
                                </div>
                                <div class="col-12">
                                    <label for="inputAddress2" class="form-label">Address 2</label>
                                    <input type="text" class="form-control" id="inputAddress2">
                                </div>
                                <div class="col-md-6">
                                    <label for="inputCity" class="form-label">City</label>
                                    <input type="text" class="form-control" id="inputCity">
                                </div>
                                <div class="col-md-4">
                                    <label for="inputState" class="form-label">State</label>
                                    <select id="inputState" class="form-select">
                                        <option selected="">Choose...</option>
                                        <option>...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="inputZip" class="form-label">Zip</label>
                                    <input type="text" class="form-control" id="inputZip">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="gridCheck3">
                                        <label class="form-check-label" for="gridCheck3">
                                            Check me out
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Sign in</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
