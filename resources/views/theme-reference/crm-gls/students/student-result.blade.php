<?php $page = 'student-result'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				<div class="row">

					<!-- Page Header -->
					<div class="col-md-12">
						<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
							<div class="my-auto mb-2">
								<h3 class="page-title mb-1">Student Details</h3>
								<nav>
									<ol class="breadcrumb mb-0">
										<li class="breadcrumb-item">
											<a href="{{url('index')}}">Dashboard</a>
										</li>
										<li class="breadcrumb-item">
											<a href="{{url('students')}}">Student</a>
										</li>
										<li class="breadcrumb-item active" aria-current="page">Student Details</li>
									</ol>
								</nav>
							</div>
							<div class="d-flex my-xl-auto right-content align-items-center  flex-wrap">
								<a href="#" class="btn btn-light me-2 mb-2" data-bs-toggle="modal"
									data-bs-target="#login_detail"><i class="ti ti-lock me-2"></i>Login Details</a>
								<a href="{{url('edit-student')}}" class="btn btn-primary d-flex align-items-center mb-2"><i
										class="ti ti-edit-circle me-2"></i>Edit Student</a>
							</div>
						</div>
					</div>
					<!-- /Page Header -->

				</div>

				<div class="row">

					<!-- Student Information -->
					<div class="col-xxl-3 col-lg-4 theiaStickySidebar">
						<div class="card border-white">
							<div class="card-header">
								<div class="d-flex align-items-center flex-wrap row-gap-3">
									<div
										class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
										<img src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid" alt="img">
									</div>
									<div class="overflow-hidden">
										<span class="badge badge-soft-success d-inline-flex align-items-center mb-1"><i
												class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
										<h5 class="mb-1 text-truncate">Janet Daniel</h5>
										<p class="text-primary">AD1256589</p>
									</div>
								</div>
							</div>

							<!-- Basic Information -->
							<div class="card-body">
								<h5 class="mb-3">Basic Information</h5>
								<dl class="row mb-0">
									<dt class="col-6 fw-normal mb-3">Roll No</dt>
									<dd class="col-6 text-dark mb-3">35013</dd>
									<dt class="col-6 fw-normal mb-3">Gender</dt>
									<dd class="col-6 text-dark mb-3">Female</dd>
									<dt class="col-6 fw-normal mb-3">Date Of Birth</dt>
									<dd class="col-6 text-dark mb-3">25 Jan 2008</dd>
									<dt class="col-6 fw-normal mb-3">Blood Group</dt>
									<dd class="col-6 text-dark mb-3">O +ve</dd>
									<dt class="col-6 fw-normal mb-3">Blood Group</dt>
									<dd class="col-6 text-dark mb-3">Red</dd>
									<dt class="col-6 fw-normal mb-3">Reigion</dt>
									<dd class="col-6 text-dark mb-3">Christianity</dd>
									<dt class="col-6 fw-normal mb-3">Caste</dt>
									<dd class="col-6 text-dark mb-3">Catholic</dd>
									<dt class="col-6 fw-normal mb-3">Category</dt>
									<dd class="col-6 text-dark mb-3">OBC</dd>
									<dt class="col-6 fw-normal mb-3">Mother tongue</dt>
									<dd class="col-6 text-dark mb-3">English</dd>
									<dt class="col-6 fw-normal mb-3">Language</dt>
									<dd class="col-6 text-dark mb-3"><span
											class="badge badge-light text-dark me-2">English</span><span
											class="badge badge-light text-dark">Spanish</span></dd>
								</dl>
								<a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect"
									class="btn btn-primary btn-sm w-100">Add Fees</a>
							</div>
							<!-- /Basic Information -->

						</div>

						<!-- Primary Contact Info -->
						<div class="card border-white">
							<div class="card-body">
								<h5 class="mb-3">Primary Contact Info</h5>
								<div class="d-flex align-items-center mb-3">
									<span
										class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
											class="ti ti-phone"></i></span>
									<div>
										<span class="fs-12 mb-1">Phone Number</span>
										<p class="text-dark">+1 46548 84498</p>
									</div>
								</div>
								<div class="d-flex align-items-center">
									<span
										class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
											class="ti ti-mail"></i></span>
									<div>
										<span class="fs-12 mb-1">Email Address</span>
										<p class="text-dark">jan@example.com</p>
									</div>
								</div>
							</div>
						</div>
						<!-- /Primary Contact Info -->

						<!-- Sibiling Information -->
						<div class="card border-white">
							<div class="card-body">
								<h5 class="mb-3">Sibiling Information</h5>
								<div class="d-flex align-items-center bg-light-300 rounded p-3 mb-3">
									<span class="avatar avatar-lg">
										<img src="{{URL::asset('build/img/students/student-06.jpg')}}" class="img-fluid rounded"
											alt="img">
									</span>
									<div class="ms-2">
										<h5>Ralph Claudia</h5>
										<p>III, B</p>
									</div>
								</div>
								<div class="d-flex align-items-center bg-light-300 rounded p-3">
									<span class="avatar avatar-lg">
										<img src="{{URL::asset('build/img/students/student-07.jpg')}}" class="img-fluid rounded"
											alt="img">
									</span>
									<div class="ms-2">
										<h5>Julie Scott</h5>
										<p>V, A</p>
									</div>
								</div>
							</div>
						</div>
						<!-- /Sibiling Information -->

						<!-- Transport Information -->
						<div class="card border-white">
							<div class="card-body pb-1">
								<ul class="nav nav-tabs nav-tabs-bottom mb-3">
									<li class="nav-item"><a class="nav-link active" href="#hostel"
											data-bs-toggle="tab">Hostel</a></li>
									<li class="nav-item"><a class="nav-link" href="#transport"
											data-bs-toggle="tab">Transportation</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade show active" id="hostel">
										<div class="d-flex align-items-center mb-3">
											<span
												class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
													class="ti ti-building-fortress fs-16"></i></span>
											<div>
												<h6 class="mb-1">HI-Hostel, Floor</h6>
												<p class="text-primary">Room No : 25</p>
											</div>
										</div>
									</div>
									<div class="tab-pane fade" id="transport">
										<div class="d-flex align-items-center mb-3">
											<span
												class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
													class="ti ti-bus fs-16"></i></span>
											<div>
												<span class="fs-12 mb-1">Route</span>
												<p class="text-dark">Newyork</p>
											</div>
										</div>
										<div class="row">
											<div class="col-sm-6">
												<div class="mb-3">
													<span class="fs-12 mb-1">Bus Number</span>
													<p class="text-dark">AM 54548</p>
												</div>
											</div>
											<div class="col-sm-6">
												<div class="mb-3">
													<span class="fs-12 mb-1">Pickup Point</span>
													<p class="text-dark">Cincinatti</p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- /Transport Information -->

					</div>
					<!-- /Student Information -->

					<div class="col-xxl-9 col-lg-8">
						<div class="row">
							<div class="col-md-12">

								<!-- List -->
								<ul class="nav nav-tabs nav-tabs-bottom mb-4">
									<li>
										<a href="{{url('student-details')}}" class="nav-link"><i
												class="ti ti-school me-2"></i>Student Details</a>
									</li>
									<li>
										<a href="{{url('student-time-table')}}" class="nav-link"><i
												class="ti ti-table-options me-2"></i>Time Table</a>
									</li>
									<li>
										<a href="{{url('student-leaves')}}" class="nav-link"><i
												class="ti ti-calendar-due me-2"></i>Leave & Attendance</a>
									</li>
									<li>
										<a href="{{url('student-fees')}}" class="nav-link"><i
												class="ti ti-report-money me-2"></i>Fees</a>
									</li>
									<li>
										<a href="{{url('student-result')}}" class="nav-link active"><i
												class="ti ti-bookmark-edit me-2"></i>Exam & Results</a>
									</li>
									<li>
										<a href="{{url('student-library')}}" class="nav-link"><i
												class="ti ti-books me-2"></i>Library</a>
									</li>
								</ul>
								<!-- /List -->

								<div class="card">
									<div
										class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
										<h4 class="mb-3">Exams & Results</h4>
										<div class="d-flex align-items-center flex-wrap">
											<div class="dropdown mb-3 me-2">
												<a href="javascript:void(0);"
													class="btn btn-outline-light bg-white dropdown-toggle"
													data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
														class="ti ti-calendar-due me-2"></i>Year : 2024 / 2025</a>
												<ul class="dropdown-menu p-3">
													<li>
														<a href="javascript:void(0);" class="dropdown-item rounded-1">
															Year : 2024 / 2025
														</a>
													</li>
													<li>
														<a href="javascript:void(0);" class="dropdown-item rounded-1">
															Year : 2023 / 2024
														</a>
													</li>
													<li>
														<a href="javascript:void(0);" class="dropdown-item rounded-1">
															Year : 2022 / 2023
														</a>
													</li>
												</ul>
											</div>
										</div>
									</div>
									<div class="card-body">

										<div class="accordions-items-seperate" id="accordionExample">
											<div class="accordion-item">
												<h2 class="accordion-header">
													<button class="accordion-button" type="button"
														data-bs-toggle="collapse" data-bs-target="#collapseOne"
														aria-expanded="true" aria-controls="collapseOne">
														<span class="avatar avatar-sm bg-success me-2"><i
																class="ti ti-checks"></i></span>
														Monthly Test (May)
													</button>
												</h2>
												<div id="collapseOne" class="accordion-collapse collapse show"
													data-bs-parent="#accordionExample">
													<div class="accordion-body">
														<!-- Exam Result List -->
														<div class="table-responsive">
															<table class="table">
																<thead class="thead-light">
																	<tr>
																		<th>Subject</th>
																		<th>Max Marks</th>
																		<th>Min Marks</th>
																		<th>Marks Obtained</th>
																		<th class="text-end">Result</th>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																		<td>English (150)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>65</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Mathematics (214)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>73</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Physics (120)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>55</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Chemistry (110)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>90</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Spanish (140)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>88</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td class="bg-dark text-white">Rank : 30</td>
																		<td class="bg-dark text-white">Total : 500</td>
																		<td class="bg-dark text-white" colspan="2">Marks
																			Obtained : 395</td>
																		<td class="bg-dark text-white text-end">
																			<div
																				class="d-flex align-items-center justify-content-end">
																				<span class="me-2">Percentage :
																					79.50</span>
																				<h6 class="fw-normal text-white">Result
																					: <span class="text-success"> Pass
																					</span></h6>
																			</div>
																		</td>
																	</tr>
																</tbody>
															</table>
														</div>
														<!-- /Exam Resul List -->
													</div>
												</div>
											</div>
											<div class="accordion-item">
												<h2 class="accordion-header">
													<button class="accordion-button collapsed" type="button"
														data-bs-toggle="collapse" data-bs-target="#collapseTwo"
														aria-expanded="false" aria-controls="collapseTwo">
														<span class="avatar avatar-sm bg-success me-2"><i
																class="ti ti-checks"></i></span>
														Monthly Test (Apr)
													</button>
												</h2>
												<div id="collapseTwo" class="accordion-collapse collapse"
													data-bs-parent="#accordionExample">
													<div class="accordion-body">
														<!-- Exam Result List -->
														<div class="table-responsive">
															<table class="table">
																<thead class="thead-light">
																	<tr>
																		<th>Subject</th>
																		<th>Max Marks</th>
																		<th>Min Marks</th>
																		<th>Marks Obtained</th>
																		<th class="text-end">Result</th>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																		<td>English (150)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>59</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Mathematics (214)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>69</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Physics (120)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>79</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Chemistry (110)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>89</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Spanish (140)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>99</td>
																		<td class="text-end">
																			<span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		<td>
																	</tr>
																	<tr>
																		<td class="bg-dark text-white">Rank : 30</td>
																		<td class="bg-dark text-white">Total : 500</td>
																		<td class="bg-dark text-white" colspan="2">Marks
																			Obtained : 400</td>
																		<td class="bg-dark text-white text-end">
																			<div
																				class="d-flex align-items-center justify-content-end">
																				<span class="me-2">Percentage :
																					80.50</span>
																				<h6 class="fw-normal text-white">Result
																					: <span class="text-success"> Pass
																					</span></h6>
																			</div>
																		</td>
																	</tr>
																</tbody>
															</table>
														</div>
														<!-- /Exam Resul List -->
													</div>
												</div>
											</div>
											<div class="accordion-item">
												<h2 class="accordion-header">
													<button class="accordion-button collapsed" type="button"
														data-bs-toggle="collapse" data-bs-target="#collapseThree"
														aria-expanded="false" aria-controls="collapseThree">
														<span class="avatar avatar-sm bg-success me-2"><i
																class="ti ti-checks"></i></span>
														Monthly Test (Mar)
													</button>
												</h2>
												<div id="collapseThree" class="accordion-collapse collapse"
													data-bs-parent="#accordionExample">
													<div class="accordion-body">
														<!-- Exam Result List -->
														<div class="table-responsive">
															<table class="table">
																<thead class="thead-light">
																	<tr>
																		<th>Subject</th>
																		<th>Max Marks</th>
																		<th>Min Marks</th>
																		<th>Marks Obtained</th>
																		<th class="text-end">Result</th>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																		<td>English (150)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>40</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Mathematics (214)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>45</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Physics (120)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>30</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Chemistry (110)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>28</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Spanish (140)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>50</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td class="bg-dark text-white">Rank : 30</td>
																		<td class="bg-dark text-white">Total : 500</td>
																		<td class="bg-dark text-white" colspan="2">Marks
																			Obtained : 250</td>
																		<td class="bg-dark text-white text-end">
																			<div
																				class="d-flex align-items-center justify-content-end">
																				<span class="me-2">Percentage :
																					50</span>
																				<h6 class="text-white fw-normal">Result
																					: <span class="text-danger"> Fail
																					</span></h6>
																			</div>
																		</td>
																	</tr>
																</tbody>
															</table>
														</div>
														<!-- /Exam Resul List -->
													</div>
												</div>
											</div>
											<div class="accordion-item">
												<h2 class="accordion-header">
													<button class="accordion-button collapsed" type="button"
														data-bs-toggle="collapse" data-bs-target="#collapseFour"
														aria-expanded="false" aria-controls="collapseFour">
														<span class="avatar avatar-sm bg-success me-2"><i
																class="ti ti-checks"></i></span>
														Monthly Test (Feb)
													</button>
												</h2>
												<div id="collapseFour" class="accordion-collapse collapse"
													data-bs-parent="#accordionExample">
													<div class="accordion-body">
														<!-- Exam Result List -->
														<div class="table-responsive">
															<table class="table">
																<thead class="thead-light">
																	<tr>
																		<th>Subject</th>
																		<th>Max Marks</th>
																		<th>Min Marks</th>
																		<th>Marks Obtained</th>
																		<th class="text-end">Result</th>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																		<td>English (150)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>40</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Mathematics (214)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>45</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Physics (120)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>30</td>
																		<td class="text-end"><span
																				class="badge badge-soft-danger d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Fail</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Chemistry (110)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>28</td>
																		<td class="text-end"><span
																				class="badge badge-soft-danger d-inline-flex align-items-center "><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Fail</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Spanish (140)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>50</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td class="bg-dark text-white">Rank : 30</td>
																		<td class="bg-dark text-white">Total : 500</td>
																		<td class="bg-dark text-white" colspan="2">Marks
																			Obtained : 250</td>
																		<td class="bg-dark text-white text-end">
																			<div
																				class="d-flex align-items-center justify-content-end">
																				<span class="me-2">Percentage :
																					50</span>
																				<h6 class="text-white">Result : <span
																						class="text-danger"> Fail
																					</span></h6>
																			</div>
																		</td>
																	</tr>
																</tbody>
															</table>
														</div>
														<!-- /Exam Resul List -->
													</div>
												</div>
											</div>
											<div class="accordion-item">
												<h2 class="accordion-header">
													<button class="accordion-button collapsed" type="button"
														data-bs-toggle="collapse" data-bs-target="#collapseFive"
														aria-expanded="false" aria-controls="collapseFive">
														<span class="avatar avatar-sm bg-success me-2"><i
																class="ti ti-checks"></i></span>
														Monthly Test (Jan)
													</button>
												</h2>
												<div id="collapseFive" class="accordion-collapse collapse"
													data-bs-parent="#accordionExample">
													<div class="accordion-body">
														<!-- Exam Result List -->
														<div class="table-responsive">
															<table class="table">
																<thead class="thead-light">
																	<tr>
																		<th>Subject</th>
																		<th>Max Marks</th>
																		<th>Min Marks</th>
																		<th>Marks Obtained</th>
																		<th class="text-end">Result</th>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																		<td>English (150)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>59</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Mathematics (214)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>69</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Physics (120)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>79</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Chemistry (110)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>89</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td>Spanish (140)</td>
																		<td>100</td>
																		<td>35</td>
																		<td>99</td>
																		<td class="text-end"><span
																				class="badge badge-soft-success d-inline-flex align-items-center"><i
																					class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
																		</td>
																	</tr>
																	<tr>
																		<td class="bg-dark text-white">Rank : 30</td>
																		<td class="bg-dark text-white">Total : 500</td>
																		<td class="bg-dark text-white" colspan="2">Marks
																			Obtained : 400</td>
																		<td class="bg-dark text-white text-end">
																			<div
																				class="d-flex align-items-center justify-content-end">
																				<span class="me-2">Percentage :
																					80.50</span>
																				<h6 class="fw-normal text-white">Result
																					: <span class="text-success"> Pass
																					</span></h6>
																			</div>
																		</td>
																	</tr>
																</tbody>
															</table>
														</div>
														<!-- /Exam Resul List -->
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
