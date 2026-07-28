<?php $page = 'ui-buttons-group'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
		@component('components.breadcrumb')
			@slot('title')
		Buttons Group
			@endslot
		@endcomponent	
		<!-- start row -->
		<div class="row">

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Basic Button Group</h5>
					</div>
					<div class="card-body">
						<div class="btn-group" role="group" aria-label="Basic example">
							<button class="btn btn-primary" type="button">Left</button>
							<button class="btn btn-primary" type="button">Middle</button>
							<button class="btn btn-primary" type="button">Right</button>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Outline button group</h5>
					</div>
					<div class="card-body">
						<div class="btn-group" role="group" aria-label="Basic example">
							<button class="btn btn-outline-primary" type="button">Left</button>
							<button class="btn btn-outline-primary" type="button">Middle</button>
							<button class="btn btn-outline-primary" type="button">Right</button>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

		</div>
		<!-- end row -->

		<!-- start row -->
		<div class="row">

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Mixed Styles</h5>
					</div>
					<div class="card-body">
						<div class="btn-group" role="group" aria-label="Basic mixed styles example">
							<button type="button" class="btn btn-danger">Left</button>
							<button type="button" class="btn btn-warning">Middle</button>
							<button type="button" class="btn btn-success">Right</button>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Nesting</h5>
					</div>
					<div class="card-body">
						<div class="btn-group" role="group" aria-label="Button group with nested dropdown">
							<button type="button" class="btn btn-primary">1</button>
							<button type="button" class="btn btn-primary">2</button>
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
									Dropdown
								</button>
								<ul class="dropdown-menu">
									<li><a class="dropdown-item" href="#">Dropdown link</a></li>
									<li><a class="dropdown-item" href="#">Dropdown link</a></li>
								</ul>
							</div>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Checkbox Button Groups</h5>
					</div>
					<div class="card-body">
						<div class="btn-group flex-wrap" role="group" aria-label="Basic checkbox toggle button group">
							<input type="checkbox" class="btn-check" id="btncheck1" checked>
							<label class="btn btn-outline-primary" for="btncheck1">Checkbox 1</label>
							<input type="checkbox" class="btn-check" id="btncheck2">
							<label class="btn btn-outline-primary" for="btncheck2">Checkbox 2</label>
							<input type="checkbox" class="btn-check" id="btncheck3">
							<label class="btn btn-outline-primary" for="btncheck3">Checkbox 3</label>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Radio Button Groups</h5>
					</div>
					<div class="card-body radio-button-group">
						<div class="btn-group" role="group" aria-label="Basic radio toggle button group">
							<input type="radio" class="btn-check" name="btnradio" id="btnradio1" checked>
							<label class="btn btn-outline-primary" for="btnradio1">Radio 1</label>

							<input type="radio" class="btn-check" name="btnradio" id="btnradio2">
							<label class="btn btn-outline-primary" for="btnradio2">Radio 2</label>

							<input type="radio" class="btn-check" name="btnradio" id="btnradio3">
							<label class="btn btn-outline-primary" for="btnradio3">Radio 3</label>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

		</div>
		<!-- end row -->

		<!-- start row -->
		<div class="row">

			<div class="col-xl-12">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Sizing</h5>
					</div>
					<div class="card-body">
						<div class="btn-group btn-group-lg mb-3" role="group" aria-label="Large button group">
							<button type="button" class="btn btn-primary">Left</button>
							<button type="button" class="btn btn-primary">Middle</button>
							<button type="button" class="btn btn-primary">Right</button>
						</div>
						<br>
						<div class="btn-group mb-3" role="group" aria-label="Default button group">
							<button type="button" class="btn btn-secondary">Left</button>
							<button type="button" class="btn btn-secondary">Middle</button>
							<button type="button" class="btn btn-secondary">Right</button>
						</div>
						<br>
						<div class="btn-group btn-group-sm" role="group" aria-label="Small button group">
							<button type="button" class="btn btn-light">Left</button>
							<button type="button" class="btn btn-light">Middle</button>
							<button type="button" class="btn btn-light">Right</button>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

			<div class="col-xl-6">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title">Button Toolbar</h5>
					</div>
					<div class="card-body">
						<div class="btn-toolbar mb-3" role="toolbar" aria-label="Toolbar with button groups">
							<div class="btn-group me-2 my-1" role="group" aria-label="First group">
								<button type="button" class="btn btn-primary">1</button>
								<button type="button" class="btn btn-primary">2</button>
								<button type="button" class="btn btn-primary">3</button>
								<button type="button" class="btn btn-primary">4</button>
							</div>
							<div class="btn-group me-2 my-1" role="group" aria-label="Second group">
								<button type="button" class="btn btn-secondary">5</button>
								<button type="button" class="btn btn-secondary">6</button>
								<button type="button" class="btn btn-secondary">7</button>
							</div>
							<div class="btn-group my-1" role="group" aria-label="Third group">
								<button type="button" class="btn btn-info">8</button>
							</div>
						</div>
						<div class="btn-toolbar mb-3" role="toolbar" aria-label="Toolbar with button groups">
							<div class="btn-group me-2 my-1" role="group" aria-label="First group">
								<button type="button" class="btn btn-outline-secondary">1</button>
								<button type="button" class="btn btn-outline-secondary">2</button>
								<button type="button" class="btn btn-outline-secondary">3</button>
								<button type="button" class="btn btn-outline-secondary">4</button>
							</div>
							<div class="input-group my-1">
								<div class="input-group-text" id="btnGroupAddon">@</div>
								<input type="text" class="form-control" placeholder="Input group example" aria-label="Input group example" aria-describedby="btnGroupAddon">
							</div>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card-->
			</div> <!-- end col -->

			<div class="col-xl-6">
				<div class="card card-h-100">
					<div class="card-header">
						<h5 class="card-title">Vertical</h5>
					</div>
					<div class="card-body">
						<div class="row g-0 row-gap-2">
							<div class="col-sm-3">
								<div class="btn-group-vertical" role="group" aria-label="Vertical button group">
									<button type="button" class="btn btn-primary">Button</button>
									<div class="btn-group" role="group">
										<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
											Dropdown
										</button>
										<ul class="dropdown-menu">
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
										</ul>
									</div>
									<div class="btn-group dropstart" role="group">
										<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
											Dropdown
										</button>
										<ul class="dropdown-menu">
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
										</ul>
									</div>
									<div class="btn-group dropend" role="group">
										<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
											Dropdown
										</button>
										<ul class="dropdown-menu">
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
										</ul>
									</div>
									<div class="btn-group dropup" role="group">
										<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
											Dropdown
										</button>
										<ul class="dropdown-menu">
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
											<li><a class="dropdown-item" href="#">Dropdown link</a></li>
										</ul>
									</div>
								</div>
							</div>
							<div class="col-sm-2">
								<div class="btn-group-vertical" role="group" aria-label="Vertical button group">
									<button type="button" class="btn btn-primary">Button</button>
									<button type="button" class="btn btn-primary">Button</button>
									<button type="button" class="btn btn-primary">Button</button>
									<button type="button" class="btn btn-primary">Button</button>
								</div>
							</div>
							<div class="col-sm-2">
								<div class="btn-group-vertical" role="group" aria-label="Vertical radio toggle button group">
									<input type="radio" class="btn-check" name="vbtn-radio" id="vbtn-radio1" checked>
									<label class="btn btn-outline-primary" for="vbtn-radio1">Radio 1</label>
									<input type="radio" class="btn-check" name="vbtn-radio" id="vbtn-radio2">
									<label class="btn btn-outline-primary" for="vbtn-radio2">Radio 2</label>
									<input type="radio" class="btn-check" name="vbtn-radio" id="vbtn-radio3">
									<label class="btn btn-outline-primary" for="vbtn-radio3">Radio 3</label>
								</div>
							</div>
						</div>
					</div> <!-- end card body -->
				</div> <!-- end card -->
			</div> <!-- end col -->

		</div>
		<!-- end row -->
    </div>
</div>
@endsection
