<?php $page = 'edit-teacher'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
	<div class="content content-two">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Edit Teacher</h3>
				<nav>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						</li>
						<li class="breadcrumb-item">
							<a href="{{url('teachers')}}">Teachers</a>
						</li>
						<li class="breadcrumb-item active" aria-current="page">Edit Teacher</li>
					</ol>
				</nav>
			</div>
		</div>
		<!-- /Page Header -->

		<div class="row">

			<div class="col-md-12">

				<form action="{{url('teachers')}}">

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
										<div
											class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
											<i class="ti ti-photo-plus fs-16"></i>
										</div>
										<div class="profile-upload">
											<div class="profile-uploader d-flex align-items-center">
												<div class="drag-upload-btn mb-3">
													Upload
													<input type="file" class="form-control image-sign"
														multiple="">
												</div>
												<a href="javascript:void(0);"
													class="btn btn-primary mb-3">Remove</a>
											</div>
											<p class="fs-12">Upload image size 4MB, Format JPG, PNG, SVG</p>
										</div>
									</div>
								</div>
							</div>
							<div class="row row-cols-xxl-5 row-cols-md-6">
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Teacher ID</label>
										<input type="text" class="form-control" value="T849126">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">First Name</label>
										<input type="text" class="form-control" value="Teresa">
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
											<option selected>III A</option>
											<option>II (A)</option>
											<option>VI (A)</option>
											<option>VIII</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Subject</label>
										<select class="select">
											<option>Select</option>
											<option selected>Physics</option>
											<option>Computer</option>
											<option>English</option>
											<option>Spanish</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Gender</label>
										<select class="select">
											<option>Select</option>
											<option>Male</option>
											<option selected>Female</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Primary Contact Number</label>
										<input type="text" class="form-control" value="+1 46548 84498">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Email Address</label>
										<input type="email" class="form-control" value="jan@example.com">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Blood Group</label>
										<select class="select">
											<option>Select</option>
											<option selected>O +ve</option>
											<option>B +ve</option>
											<option>B -ve</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Date of Joining</label>
										<input type="text" class="form-control datetimepicker"
											value="15/12/1992">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Father’s Name</label>
										<input type="text" class="form-control" value="Francis Saviour">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Mother’s Name</label>
										<input type="text" class="form-control" value="Stella Bruce">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Date of Birth</label>
										<input type="text" class="form-control datetimepicker"
											value="15/12/1992">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Marital Status</label>
										<select class="select">
											<option>Select</option>
											<option selected>Single</option>
											<option>Married</option>
										</select>
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Language Known</label>
										<input class="input-tags form-control" type="text" data-role="tagsinput"
											name="Label" value="English, Spanish">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Qualification</label>
										<input type="text" class="form-control" value="MBA">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Work Experience</label>
										<input type="text" class="form-control" value="2  Years">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Previous School if Any</label>
										<input type="text" class="form-control"
											value="Oxford Matriculation, USA">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Previous School Address</label>
										<input type="text" class="form-control"
											value="1852 Barnes Avenue, Cincinnati, OH 45202">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Previous School Phone No</label>
										<input type="text" class="form-control" value="+1 35676 45556">
									</div>
								</div>
								<div class="col-xxl-3 col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Address</label>
										<input type="text" class="form-control"
											value="3495 Red Hawk Road, Buffalo Lake, MN 55314">
									</div>
								</div>
								<div class="col-xxl-3 col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Permanent Address</label>
										<input type="text" class="form-control"
											value="3495 Red Hawk Road, Buffalo Lake, MN 55314">
									</div>
								</div>
								<div class="col-xxl-3 col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">PAN Number / ID Number</label>
										<input type="text" class="form-control" value="343445954908">
									</div>
								</div>
								<div class="col-xxl-3 col-xl-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option selected>Active</option>
											<option>Inactive</option>
										</select>
									</div>
								</div>
								<div class="col-xl-12">
									<div class="mb-3">
										<label class="form-label">Notes</label>
										<textarea class="form-control" placeholder="Other Information"
											rows="4">Depending on the specific needs of your organization or system, additional information may be collected or tracked. It's important to ensure that any data collected complies with privacy regulations and policies to protect students' sensitive information.</textarea>
									</div>
								</div>
							</div>

						</div>
					</div>
					<!-- /Personal Information -->

					<!-- Payroll -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-user-shield fs-16"></i>
								</span>
								<h4 class="text-dark">Payroll</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="row">
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">EPF No</label>
										<input type="text" class="form-control" value="34234345">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Basic Salary</label>
										<input type="text" class="form-control" value="150000">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Contract Type</label>
										<select class="select">
											<option>Select</option>
											<option selected>Permanent</option>
											<option>Temporary</option>
										</select>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Work Shift</label>
										<select class="select">
											<option>Select</option>
											<option selected>Day Shift</option>
											<option>Night Shift</option>
										</select>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Work Location</label>
										<input type="text" class="form-control" value="2nd Floor">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Date of Leaving</label>
										<input type="text" class="form-control datetimepicker">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Payroll -->

					<!-- Leaves -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-users fs-16"></i>
								</span>
								<h4 class="text-dark">Leaves</h4>
							</div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-lg-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Medical Leaves</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-lg-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Casual Leaves</label>
										<input type="text" class="form-control" value="02">
									</div>
								</div>
								<div class="col-lg-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Maternity Leaves</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-lg-3 col-md-6">
									<div class="mb-3">
										<label class="form-label">Sick Leaves</label>
										<input type="text" class="form-control">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Leaves -->

					<!-- Bank Details -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-map fs-16"></i>
								</span>
								<h4 class="text-dark">Bank Account Detail</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="row">
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Account Name</label>
										<input type="text" class="form-control" value="Teresa">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Account Number</label>
										<input type="text" class="form-control" value="0126784900">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Bank Name</label>
										<input type="text" class="form-control" value="Bank of America">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">IFSC Code</label>
										<input type="text" class="form-control" value="BOA83209832">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Branch Name</label>
										<input type="text" class="form-control" value="Cincinnati">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Bank Details -->

					<!-- Transport Information -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-bus-stop fs-16"></i>
								</span>
								<h4 class="text-dark">Transport Information</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="row">
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Route</label>
										<select class="select">
											<option>Select</option>
											<option selected>Newyork</option>
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
											<option selected>AM 54548</option>
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
											<option selected>Cincinatti</option>
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
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-building-fortress fs-16"></i>
								</span>
								<h4 class="text-dark">Hostel Information</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Hostel</label>
										<select class="select">
											<option>Select</option>
											<option selected>HI-Hostel, Floor</option>
											<option>HI-Hostel, 2nd Floor</option>
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Room No</label>
										<select class="select">
											<option>Select</option>
											<option selected>20</option>
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

					<!-- Social Media Links -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-building fs-16"></i>
								</span>
								<h4 class="text-dark">Social Media Links</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="row rows-cols-xxl-5">
								<div class="col-xxl col-xl-3 col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Facebook</label>
										<input type="text" class="form-control" value="www.facebook.com">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Instagram</label>
										<input type="text" class="form-control" value="www.twitter.com">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Linked In</label>
										<input type="text" class="form-control" value="www.Linkedin.com">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Youtube</label>
										<input type="text" class="form-control" value="www.youtube.com">
									</div>
								</div>
								<div class="col-xxl col-xl-3 col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Twitter URL</label>
										<input type="text" class="form-control" value="www.instagram.com">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Social Media Links -->

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
						<div class="card-body pb-0">
							<div class="row">
								<div class="col-lg-6">
									<div class="mb-3">
										<div class="mb-3">
											<label class="form-label">Upload Resume</label>
											<p>Upload image size of 4MB, Accepted Format PDF</p>
										</div>
										<div class="upload-file-wrap">
											<div class="upload-file">
												<i class="ti ti-file-upload"></i>Change
												<input type="file" class="form-control image_sign">
											</div>
											<p>Resume.pdf</p>
										</div>
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-3">
										<div class="mb-3">
											<label class="form-label">Upload Joining Letter</label>
											<p>Upload image size of 4MB, Accepted Format PDF</p>
										</div>
										<div class="upload-file-wrap">
											<div class="upload-file">
												<i class="ti ti-file-upload"></i>Upload Document
												<input type="file" class="form-control image_sign">
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Documents -->

					<!-- Password -->
					<div class="card">
						<div class="card-header bg-light">
							<div class="d-flex align-items-center">
								<span class="bg-white avatar avatar-sm me-2 text-gray-7 flex-shrink-0">
									<i class="ti ti-file fs-16"></i>
								</span>
								<h4 class="text-dark">Password</h4>
							</div>
						</div>
						<div class="card-body pb-0">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">New Password</label>
										<input type="password" class="form-control">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Confirm Password</label>
										<input type="password" class="form-control">
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Password -->

					<div class="text-end">
						<button type="button" class="btn btn-light me-3">Cancel</button>
						<button type="submit" class="btn btn-primary">Edit Teacher</button>
					</div>

				</form>

			</div>

		</div>

	</div>
</div>
<!-- /Page Wrapper --> 	

@endsection
