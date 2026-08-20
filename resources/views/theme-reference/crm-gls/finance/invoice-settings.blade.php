<?php $page = 'invoice-settings'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content bg-white">
		<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">App Settings</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="javascript:void(0);">Settings</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">App Settings</li>
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
					<a href="{{url('invoice-settings')}}" class="d-block rounded p-2 active">Invoice Settings</a>
					<a href="{{url('custom-fields')}}"  class="d-block rounded p-2">Custom Fields</a>
				</div>
			</div>
			<div class="col-xxl-10 col-xl-9">
				<div class="flex-fill border-start ps-3">
					<form action="{{url('invoice-settings')}}">
						<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
							<div class="mb-3">
								<h5 class="mb-1">Invoice Settings</h5>
								<p>Collection of settings for Invoice</p>
							</div>
							<div class="mb-3">
								<button class="btn btn-light me-2" type="button">Cancel</button>
								<button class="btn btn-primary" type="submit">Save</button>
							</div>
						</div>
						<div class="d-md-flex d-block">
							<div class="row flex-fill">
								<div class="col-xl-10">
									<div class="settings-middle-info invoice-setting-wrap">
										<div class="row align-items-center mb-2">
											<div class="col-xxl-7 col-lg-6">
												<div class="invoice-info-title">
													<h6>Invoice Logo</h6>
													<p>Upload logo of you company to display in Invoice</p>
												</div>
											</div>
											<div class="col-xxl-5 col-lg-6">
												<div class="card">
													<div class="card-body">
														<div class="d-flex justify-content-between mb-3">
															<div class="d-flex align-items-center">
																<span class="avatar avatar-xl border rounded d-flex align-items-center justify-content-center p-2 me-2"><img src="{{URL::asset('build/img/logo-small.svg')}}" alt="Img"></span>
																<h5>Logo</h5>
															</div>
															<div class="d-flex align-items-center">
																<a href="javascript:void(0);" class="text-primary border rounded fs-16 p-1 badge badge-primary-hover me-2"><i class="ti ti-edit-circle"></i></a>
																<a href="javascript:void(0);" class="text-danger border rounded fs-16 p-1 badge badge-danger-hover"><i class="ti ti-trash-x"></i></a>
															</div>
														</div>
														<div class="profile-uploader profile-uploader-two mb-0">
															<span class="d-block text-center lh-1 fs-24 mb-1"><i class="ti ti-upload"></i></span>
															<div class="drag-upload-btn bg-transparent me-0 border-0">
																<p class="fs-12 mb-2"><span class="text-primary">Click to Upload</span> or drag and drop
																</p>
																<h6>JPG or PNG</h6>
																<h6>(Max 450 x 450 px)</h6>
															</div>
															<input type="file" class="form-control" multiple="" id="image_sign">
															<div id="frames"></div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-7 col-lg-6">
													<div class="mb-3">
														<h6>Invoice Prefix</h6>
														<p>Add prefix to your Invoice</p>
													</div>
												</div>
												<div class="col-xxl-5 col-lg-6">
													<div class="mb-3">
														<input type="text" class="form-control">
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-7 col-lg-6">
													<div class="mb-3">
														<h6>Invoice Due</h6>
														<p>Select due date to display in Invoice </p>
													</div>
												</div>
												<div class="col-xxl-5 col-lg-6">
													<div class="mb-3 d-flex align-items-center">
														<div class="w-100">
															<select class="select">
																<option>5</option>
																<option>4</option>
																<option>3</option>
															</select>
														</div>
														<span class="ms-3 d-block">Days</span>
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-7 col-lg-6">
													<div class="mb-3">
														<h6>Invoice Round Off</h6>
														<p>Value round off in invoice</p>
													</div>
												</div>
												<div class="col-xxl-5 col-lg-6">
													<div class="mb-3 d-flex align-items-center">
														<div class="w-100">
															<select class="select">
																<option>Roundoff Up</option>
																<option>Roundoff Up</option>
															</select>
														</div>
														<div class="status-toggle modal-status ms-3">
															<input type="checkbox" id="user1" class="check">
															<label for="user1" class="checktoggle"> </label>
														</div>	
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-7 col-lg-6">
													<div class="mb-3">
														<h6>Show Company Details</h6>
														<p>Show/hide company details in invoice</p>
													</div>
												</div>
												<div class="col-xxl-5 col-lg-6">
													<div class="mb-3">
														<div class="status-toggle modal-status">
															<input type="checkbox" id="user2" class="check">
															<label for="user2" class="checktoggle"> </label>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-7 col-lg-6">
													<div class="mb-3">
														<h6>Invoice Header Terms</h6>
														<p>Header Terms</p>
													</div>
												</div>
												<div class="col-xxl-5 col-lg-6">
													<div class="mb-3">
														<textarea rows="4" class="form-control" placeholder="Add Comment"></textarea>
													</div>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded">
											<div class="row align-items-center flex-fill">
												<div class="col-xxl-7 col-lg-6">
													<div class="mb-3">
														<h6>Invoice Footer Terms</h6>
														<p>Footer Terms</p>
													</div>
												</div>
												<div class="col-xxl-5 col-lg-6">
													<div class="mb-3">
														<textarea rows="4" class="form-control" placeholder="Add Comment"></textarea>
													</div>
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
