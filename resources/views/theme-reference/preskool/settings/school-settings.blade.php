<?php $page = 'school-settings'; ?>
@extends('layout.mainlayout')
@section('content')
  	<!-- Page Wrapper -->
	  <div class="page-wrapper">
		<div class="content bg-white">
			<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
				<div class="my-auto mb-2">
					<h3 class="page-title mb-1">Academic Settings</h3>
					 <nav>
						   <ol class="breadcrumb mb-0">
							  <li class="breadcrumb-item">
								<a href="{{url('index')}}">Dashboard</a>
							  </li>
							  <li class="breadcrumb-item">
								<a href="javascript:void(0);">Settings</a>
							  </li>
							  <li class="breadcrumb-item active" aria-current="page">Academic Settings</li>
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
						<a href="{{url('school-settings')}}" class="d-block rounded active p-2">School Settings</a>
						<a href="{{url('religion')}}" class="d-block rounded p-2">Religion</a>
					</div>
				</div>
				<div class="col-xxl-10 col-xl-9">
					<div class="border-start ps-3">
						<form action="{{url('school-settings')}}">
							<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
								<div class="mb-3">
									<h5 class="mb-1">School Settings</h5>
									<p>School Settings Configuration</p>
								</div>
								<div class="mb-3">
									<button class="btn btn-light me-2" type="button">Cancel</button>
									<button class="btn btn-primary" type="submit">Save</button>
								</div>
							</div>
							<div class="d-md-flex">
								<div class="row flex-fill">
									<div class="col-xl-10">
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-8 col-lg-6">
													<div class="mb-3">
														<h6>School Name</h6>
														<p>Shows name of your school</p>
													</div>
												</div>
												<div class="col-xxl-4 col-lg-6">
													<div class="mb-3">
														<input type="text" class="form-control" placeholder="Enter School Name">
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-8 col-lg-6">
													<div class="mb-3">
														<h6>Phone Number</h6>
														<p>Shows phone number of your school</p>
													</div>
												</div>
												<div class="col-xxl-4 col-lg-6">
													<div class="mb-3">
														<input type="text" class="form-control" placeholder="Enter Phone Number">
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-8 col-lg-6">
													<div class="mb-3">
														<h6>Email</h6>
														<p>Shows email of your school</p>
													</div>
												</div>
												<div class="col-xxl-4 col-lg-6">
													<div class="mb-3">
														<input type="email" class="form-control" placeholder="Enter Email">
													</div>
												</div>
											</div>
										</div>	
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-8 col-lg-6">
													<div class="mb-3">
														<h6>Fax</h6>
														<p>Shows fax of your school</p>
													</div>
												</div>
												<div class="col-xxl-4 col-lg-6">
													<div class="mb-3">
														<input type="text" class="form-control" placeholder="Enter Fax">
													</div>
												</div>
											</div>
										</div>	
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-8 col-lg-6">
													<div class="mb-3">
														<h6>Address</h6>
														<p>Shows address of your school</p>
													</div>
												</div>
												<div class="col-xxl-4 col-lg-6">
													<div class="mb-3">
														<textarea rows="4" class="form-control" placeholder="Add Comment"></textarea>
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
