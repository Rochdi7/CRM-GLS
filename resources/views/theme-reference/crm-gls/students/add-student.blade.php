<?php $page = 'add-student'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
	<div class="content content-two">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="mb-1">Add Student</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="{{url('students')}}">Students</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">Add Student</li>
					</ol>
				  </nav>
			</div>
		</div>
		<!-- /Page Header -->
		
		<div class="row">

			<div class="col-md-12">

				<form action="{{url('students')}}">

					<!-- Personal Information -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-info-square-rounded fs-16"></i>
								</span>
								<h4 class="text-dark">Personal Information</h4>
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-md-12">
									<div class="d-flex align-items-center flex-wrap row-gap-3 mb-3">                                                
										<div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
											<i class="ti ti-photo-plus fs-16"></i>
										</div>                                              
										<div class="profile-upload">
											<div class="profile-uploader d-flex align-items-center">
												<div class="drag-upload-btn mb-3">
													Upload
													<input type="file" class="form-control image-sign" multiple="">
												</div>
												<a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
											</div>
											<p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
										</div>
									</div>
								</div>
							</div>
							<div class="row row-cols-xxl-5 row-cols-md-6">
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Academic Year</label>
										<select class="select">
											<option>June 2024/25</option>
											<option>June 2023/24</option>
											<option>June 2022/23</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Admission Number</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Admission Date</label>
										<div class="input-icon position-relative">
											<span class="input-icon-addon">
												<i class="ti ti-calendar"></i>
											</span>
											<input type="text" class="form-control datetimepicker">
										</div>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Roll Number</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option>Active</option>
											<option>Inactive</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">First Name</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Last Name</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Class</label>
										<select class="select">
											<option>Select</option>
											<option>I</option>
											<option>II</option>
											<option>III</option>
											<option>IV</option>
											<option>V</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Section</label>
										<select class="select">
											<option>Select</option>
											<option>A</option>
											<option>B</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Gender</label>
										<select class="select">
											<option>Select</option>
											<option>Male</option>
											<option>Female</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Date of Birth</label>
										<div class="input-icon position-relative">
											<span class="input-icon-addon">
												<i class="ti ti-calendar"></i>
											</span>
											<input type="text" class="form-control datetimepicker">
										</div>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Blood Group</label>
										<select class="select">
											<option>Select</option>
											<option>O +ve</option>
											<option>B +ve</option>
											<option>B -ve</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">House</label>
										<select class="select">
											<option>Select</option>
											<option>Red</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Religion</label>
										<select class="select">
											<option>Select</option>
											<option>Christianity</option>
											<option>Buddhism</option>
											<option>Irreligion</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Category</label>
										<select class="select">
											<option>Select</option>
											<option>OBC</option>
											<option>BC</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Primary Contact Number</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Email Address</label>
										<input type="email" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Caste</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Mother Tongue</label>
										<select class="select">
											<option>Select</option>
											<option>English</option>
											<option>Spanish</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Language Known</label>
										<input class="input-tags form-control" type="text" data-role="tagsinput"  name="Label" value="English, Spanish">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Personal Information -->

					<!-- Parents & Guardian Information -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-user-shield fs-16"></i>
								</span>
								<h4 class="text-dark">Parents & Guardian Information</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="border-bottom mb-3">
								<h5 class="mb-3">Father’s Info</h5>
								<div class="row">
									<div class="col-md-12">
										<div class="d-flex align-items-center flex-wrap row-gap-3 mb-3">                                                
											<div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
												<i class="ti ti-photo-plus fs-16"></i>
											</div>                                              
											<div class="profile-upload">
												<div class="profile-uploader d-flex align-items-center">
													<div class="drag-upload-btn mb-3">
														Upload
														<input type="file" class="form-control image-sign" multiple="">
													</div>
													<a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
												</div>
												<p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
											</div>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Father Name</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Email</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Phone Number</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Father Occupation</label>
											<input type="text" class="form-control">
										</div>
									</div>
								</div>
							</div>
							<div class="border-bottom mb-3">
								<h5 class="mb-3">Mother’s Info</h5>
								<div class="row">
									<div class="col-md-12">
										<div class="d-flex align-items-center flex-wrap row-gap-3 mb-3">                                                
											<div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
												<i class="ti ti-photo-plus fs-16"></i>
											</div>                                              
											<div class="profile-upload">
												<div class="profile-uploader d-flex align-items-center">
													<div class="drag-upload-btn mb-3">
														Upload
														<input type="file" class="form-control image-sign" multiple="">
													</div>
													<a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
												</div>
												<p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
											</div>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Mother Name</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Email</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Phone Number</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Mother Occupation</label>
											<input type="text" class="form-control">
										</div>
									</div>
								</div>
							</div>
							<div>
								<h5 class="mb-3">Guardian Details</h5>
								<div class="row">
									<div class="col-md-12">
										<div class="mb-2">
											<div class="d-flex align-items-center flex-wrap">
												<label class="form-label text-dark fw-normal me-2">If Guardian Is</label>
												<div class="form-check me-3 mb-2">
													<input class="form-check-input" type="radio" name="guardian" id="parents" checked="">
													<label class="form-check-label" for="parents">
														Parents
													</label>
												</div>
												<div class="form-check me-3 mb-2">
													<input class="form-check-input" type="radio" name="guardian" id="guardian">
													<label class="form-check-label" for="guardian">
														Guardian
													</label>
												</div>
												<div class="form-check mb-2">
													<input class="form-check-input" type="radio" name="guardian" id="other">
													<label class="form-check-label" for="other">
														Others
													</label>
												</div>
											</div>
										</div>
										<div class="d-flex align-items-center flex-wrap row-gap-3 mb-3">                                                
											<div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
												<i class="ti ti-photo-plus fs-16"></i>
											</div>                                              
											<div class="profile-upload">
												<div class="profile-uploader d-flex align-items-center">
													<div class="drag-upload-btn mb-3">
														Upload
														<input type="file" class="form-control image-sign" multiple="">
													</div>
													<a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
												</div>
												<p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
											</div>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Guardian Name</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Guardian Relation</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Phone Number</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Email</label>
											<input type="email" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Occupation</label>
											<input type="text" class="form-control">
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Address</label>
											<input type="text" class="form-control">
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Parents & Guardian Information -->

					<!-- Sibilings -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-users fs-16"></i>
								</span>
								<h4 class="text-dark">Sibilings</h4>
							</div>
						</div>
						<div class="card-body">
							<div class="addsibling-info">
								<div class="row">
									<div class="col-md-12">
										<div class="mb-2">
											<label class="form-label">Sibiling Info</label>
											<div class="d-flex align-items-center flex-wrap">
												<label class="form-label text-dark fw-normal me-2">Is Sibiling studying in same school</label>
												<div class="form-check me-3 mb-2">
													<input class="form-check-input" type="radio" name="sibling" id="yes" checked="">
													<label class="form-check-label" for="yes">
														Yes
													</label>
												</div>
												<div class="form-check mb-2">
													<input class="form-check-input" type="radio" name="sibling" id="no">
													<label class="form-check-label" for="no">
														No
													</label>
												</div>
											</div>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Name</label>
											<select class="select">
												<option>Select</option>
												<option>Ralph Claudia</option>
												<option>Julie Scott</option>
												<option>Maria</option>
											</select>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Roll No</label>
											<select class="select">
												<option>Select</option>
												<option>35013</option>
												<option>35011</option>
												<option>35010</option>
											</select>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<label class="form-label">Admission No</label>
											<select class="select">
												<option>Select</option>
												<option>AD9892434</option>
												<option>AD9892433</option>
												<option>AD9892432</option>
											</select>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<div class="d-flex align-items-center">
												<div class="w-100">
													<label class="form-label">Class</label>
													<select class="select w-100">
														<option>Select</option>
														<option>I</option>
														<option>II</option>
														<option>III</option>
													</select>
												</div>
												<div>
													<label class="form-label">&nbsp;</label>
													<a href="javascript:void(0);" class="trash-icon ms-3"><i class="ti ti-trash-x"></i></a>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="border-top pt-3"> 
								<a href="javascript:void(0);" class="add-sibling btn btn-primary d-inline-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Add New</a>
							</div>
						</div>
					</div>
					<!-- /Sibilings -->

					<!-- Address -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-map fs-16"></i>
								</span>
								<h4 class="text-dark">Address</h4>
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Current Address</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Permanent Address</label>
										<input type="text" class="form-control">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Address -->

					<!-- Transport Information -->
					<div class="card">
						<div class="card-header bg-light d-flex align-items-center justify-content-between">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-bus-stop fs-16"></i>
								</span>
								<h4 class="text-dark">Transport Information</h4>
							</div>
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" role="switch">
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Route</label>
										<select class="select">
											<option>Select</option>
											<option>Newyork</option>
											<option>Denver</option>
											<option>Chicago</option>
										</select>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Vehicle Number</label>
										<select class="select">
											<option>Select</option>
											<option>AM 54548</option>
											<option>AM 64528</option>
											<option>AM 123548</option>
										</select>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Pickup Point</label>
										<select class="select">
											<option>Select</option>
											<option>Cincinatti</option>
											<option>Illinois</option>
											<option>Morgan</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Transport Information -->

					<!-- Hostel Information -->
					<div class="card">
						<div class="card-header bg-light d-flex align-items-center justify-content-between">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-building-fortress fs-16"></i>
								</span>
								<h4 class="text-dark">Hostel Information</h4>
							</div>
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" role="switch">
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Hostel</label>
										<select class="select">
											<option>Select</option>
											<option>Phoenix Residence</option>
											<option>Tranquil Haven</option>
											<option>Radiant Towers</option>
											<option>Nova Nest</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Room No</label>
										<select class="select">
											<option>Select</option>
											<option>20</option>
											<option>22</option>
											<option>24</option>
											<option>26</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Hostel Information -->

					<!-- Documents -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-file fs-16"></i>
								</span>
								<h4 class="text-dark">Documents</h4>
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-lg-6">
									<div class="mb-2">
										<div class="mb-3">
											<label class="form-label mb-1">Medical Condition</label>
											<p>Upload image size of 4MB, Accepted Format PDF</p>
										</div>
										<div class="d-flex align-items-center flex-wrap">
											<div class="btn btn-primary drag-upload-btn mb-2 me-2">
												<i class="ti ti-file-upload me-1"></i>Change
												<input type="file" class="form-control image_sign" multiple="">
											</div>
											<p class="mb-2">BirthCertificate.pdf</p>
										</div>
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-2">
										<div class="mb-3">
											<label class="form-label mb-1">Upload Transfer Certificate</label>
											<p>Upload image size of 4MB, Accepted Format PDF</p>
										</div>
										<div class="d-flex align-items-center flex-wrap">
											<div class="btn btn-primary drag-upload-btn mb-2">
												<i class="ti ti-file-upload me-1"></i>Upload Document
												<input type="file" class="form-control image_sign" multiple="">
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Documents -->

					<!-- Medical History -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-medical-cross fs-16"></i>
								</span>
								<h4 class="text-dark">Medical History</h4>
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-2">
										<label class="form-label">Medical Condition</label>
										<div class="d-flex align-items-center flex-wrap">
											<label class="form-label text-dark fw-normal me-2">Medical Condition of a Student</label>
											<div class="form-check me-3 mb-2">
												<input class="form-check-input" type="radio" name="condition" id="good" checked="">
												<label class="form-check-label" for="good">
													Good
												</label>
											</div>
											<div class="form-check me-3 mb-2">
												<input class="form-check-input" type="radio" name="condition" id="bad">
												<label class="form-check-label" for="bad">
													Bad
												</label>
											</div>
											<div class="form-check mb-2">
												<input class="form-check-input" type="radio" name="condition" id="others">
												<label class="form-check-label" for="others">
													Others
												</label>
											</div>
										</div>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Allergies</label>
									<input class="input-tags form-control" type="text" data-role="tagsinput"  name="Label" value="Allergy, Skin Allergy">
								</div>
								<div class="mb-3">
									<label class="form-label">Medications</label>
									<input class="input-tags form-control" type="text" data-role="tagsinput"  name="Label" value="Medecine Name">
								</div>
							</div>
						</div>
					</div>
					<!-- /Medical History -->

					<!-- Previous School details -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-building fs-16"></i>
								</span>
								<h4 class="text-dark">Previous School Details</h4>
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">School Name</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Address</label>
										<input type="text" class="form-control">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Previous School details -->

					<!-- Other Details -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-building-bank fs-16"></i>
								</span>
								<h4 class="text-dark">Other Details</h4>
							</div>
						</div>
						<div class="card-body pb-1">
							<div class="row">
								<div class="col-md-5">
									<div class="mb-3">
										<label class="form-label">Bank Name</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-md-2">
									<div class="mb-3">
										<label class="form-label">Branch</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-md-5">
									<div class="mb-3">
										<label class="form-label">IFSC Number</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Other Information</label>
										<textarea class="form-control"  rows="3"></textarea>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Other Details -->

					<div class="text-end">
						<button type="button" class="btn btn-light me-3">Cancel</button>
						<button type="submit" class="btn btn-primary">Add Student</button>
					</div>

				</form>

			</div>

		</div>

	</div>
</div>
<!-- /Page Wrapper -->

@endsection
