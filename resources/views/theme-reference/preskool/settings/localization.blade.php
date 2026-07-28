<?php $page = 'localization'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
	<div class="content">
		<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Website Settings</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="javascript:void(0);">Settings</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">Website Settings</li>
					</ol>
				  </nav>
			</div>
			<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
				  <div class="pe-1 mb-2">
					   <a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
						 <i class="ti ti-refresh"></i>
					</a>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xxl-2 col-xl-3">
				<div class="pt-3 d-flex flex-column list-group mb-4">
					<a href="{{url('company-settings')}}" class="d-block rounded p-2">Company Settings</a>
					<a href="{{url('localization')}}" class="d-block rounded p-2 active">Localization</a>
					<a href="{{url('prefixes')}}" class="d-block rounded p-2">Prefixes</a>
					<a href="{{url('preferences')}}" class="d-block rounded p-2">Preferences</a>
					<a href="{{url('social-authentication')}}" class="d-block rounded p-2">Social Authentication</a>
					<a href="{{url('language')}}" class="d-block rounded p-2">Language</a>
				</div>
			</div>
			<div class="col-xxl-10 col-xl-9">
				<div class="border-start ps-3">
					<form action="{{url('localization')}}">
						<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
							<div class="mb-3">
								<h5 class="mb-1">Localization</h5>
								<p>Collection of settings for user environment</p>
							</div>
							<div class="mb-3">
								<button class="btn btn-light me-2" type="button">Cancel</button>
								<button class="btn btn-primary" type="submit">Save</button>
							</div>
						</div>
						<div class="d-md-flex d-block">
							<div class="flex-fill">
								<div class="card">
									<div class="card-header p-3">
										<h5>Basic Information</h5>
									</div>
									<div class="card-body p-3 pb-0">
										<div class="d-block d-xl-flex align-items-end">
											<div class="mb-3 flex-fill me-xl-3 me-0">
												<label class="form-label">Language</label>
												<select class="select">
													<option>English</option>
													<option>Spanish</option>
													<option>French</option>
												</select>
											</div>
											<div class="mb-3 flex-fill">
												<div class="d-flex align-items-center justify-content-between">
													<div class="status-title">
														<h5>Language Switcher</h5>
														<p>To display in all pages</p>
													</div>
													<div class="form-check form-switch">
														<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
													</div>
												</div>
											</div>
										</div>	
										<div class="row">
											<div class="col-xl-4 pe-xl-0">
												<div class="mb-3 flex-fill me-xl-3 me-0">
													<label class="form-label">Timezone</label>
													<select class="select">
														<option>UTC 4:30</option>
														<option>(UTC+11:00) INR</option>
													</select>
												</div>	
											</div>
											<div class="col-xl-4 ps-xl-0 pe-xl-0">
												<div class="mb-3 flex-fill  me-xl-3 me-0">
													<label class="form-label">Date Format</label>
													<select class="select">
														<option>01 Jan 2024</option>
														<option>Jan 01 2024</option>
														<option>2024 Jan 01</option>
													</select>
												</div>
											</div>
											<div class="col-xl-4 ps-xl-0">
												<div class="mb-3 flex-fill">
													<label class="form-label">Time Format</label>
													<select class="select">
														<option>12 Hours</option>
														<option>24 Hours</option>
													</select>
												</div>
											</div>
										</div>																					
										<div class="row">
											<div class="col-xl-4 pe-xl-0">
												<div class="mb-3 flex-fill me-xl-3 me-0">
													<label class="form-label">Financial Year</label>
													<select class="select">
														<option>2024</option>
														<option>2023</option>
													</select>
												</div>	
											</div>
											<div class="col-xl-4 ps-xl-0">
												<div class="mb-3 flex-fill">
													<label class="form-label">Starting Month</label>
													<select class="select">
														<option>January</option>
														<option>February</option>
														<option>March</option>
													</select>
												</div>
											</div>
											
										</div>											
									</div>
								</div>
								<div class="card">
									<div class="card-header p-3">
										<h5>Currency Settings</h5>
									</div>
									<div class="card-body p-3 pb-0">	
										<div class="row">
											<div class="col-xl-4 pe-xl-0">
												<div class="mb-3 flex-fill me-xl-3 me-0">
													<label class="form-label">Currency</label>
													<select class="select">
														<option>United States of America</option>
														<option>India</option>
													</select>
												</div>	
											</div>
											<div class="col-xl-4 ps-xl-0 pe-xl-0">
												<div class="mb-3 flex-fill  me-xl-3 me-0">
													<label class="form-label">Currency Position</label>
													<select class="select">
														<option>$</option>
														<option>₹</option>
														<option>€</option>
													</select>
												</div>
											</div>
											<div class="col-xl-4 ps-xl-0">
												<div class="mb-3 flex-fill">
													<label class="form-label">Time Format</label>
													<select class="select">
														<option>$100</option>
														<option>100$</option>
													</select>
												</div>
											</div>
										</div>																					
										<div class="row">
											<div class="col-xl-4 pe-xl-0">
												<div class="mb-3 flex-fill me-xl-3 me-0">
													<label class="form-label">Decimal Seperator</label>
													<select class="select">
														<option>.</option>
														<option>.</option>
													</select>
												</div>	
											</div>
											<div class="col-xl-4 ps-xl-0">
												<div class="mb-3 flex-fill">
													<label class="form-label">Thousand Seperator</label>
													<select class="select">
														<option>,</option>
														<option>,</option>
													</select>
												</div>
											</div>
										</div>											
									</div>
								</div>
								<div class="card">
									<div class="card-header p-3">
										<h5>Country Settings</h5>
									</div>
									<div class="card-body p-3 pb-0">	
										<div class="row align-items-center">
											<div class="col-xl-8">
												<div class="mb-3 flex-fill">
													<h6>Country Restriction</h6>
													<p>Select restrcted countries</p>
												</div>	
											</div>
											<div class="col-xl-4">
												<div class="mb-3 flex-fill">
													<select class="select">
														<option>Allow All Countries</option>
														<option>Deny All Countries</option>
													</select>
												</div>
											</div>
										</div>																																
									</div>
								</div>
								<div class="card">
									<div class="card-header p-3">
										<h5>File Settings</h5>
									</div>
									<div class="card-body p-3 pb-0">	
										<div class="row align-items-center mb-2">
											<div class="col-xl-8">
												<div class="mb-3 flex-fill">
													<h6>Allowed Files</h6>
													<p>Select allowed files</p>
												</div>	
											</div>
											<div class="col-xl-4">
												<div class="mb-3 flex-fill">
													<input class="input-tags form-control" type="text" data-role="tagsinput"  name="Label" value="JPG, GIF,PNG">
												</div>
											</div>											
										</div>	
										<div class="row">
											<div class="col-xl-8">
												<div class="mb-3 flex-fill">
													<h6>Maximum File Size</h6>
													<p>Select max size of files</p>
												</div>	
											</div>
											<div class="col-xl-4">
												<div class="mb-3 flex-fill">
													<select class="select">
														<option>5000 MB</option>
														<option>2000 MB</option>
													</select>
												</div>
											</div>
										</div>																															
									</div>
								</div>
							</div>
						</div>														
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /Page Wrapper --> 

@endsection
