<?php $page = 'events'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
	<div class="content">

		<!-- Page Header -->
		<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
			<div class="my-auto mb-2">
				<h3 class="mb-1">Events</h3>
				<nav>
					<ol class="breadcrumb mb-0">
						<li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						</li>
						<li class="breadcrumb-item">
							Announcement
						</li>
						<li class="breadcrumb-item active" aria-current="page">Events</li>
					</ol>
				</nav>
			</div>
			<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
				<div class="pe-1 mb-2">
					<a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip"
						data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
						<i class="ti ti-refresh"></i>
					</a>
				</div>
				<div class="pe-1 mb-2">
					<button type="button" class="btn btn-outline-light bg-white btn-icon me-1"
						data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print"
						data-bs-original-title="Print">
						<i class="ti ti-printer"></i>
					</button>
				</div>
				<div class="mb-2">
					<a href="#" class="btn btn-light d-flex align-items-center"><i class="ti ti-calendar-up me-2"></i>Sync with Google Calendar</a>
				</div>
			</div>
		</div>
		<!-- /Page Header -->

		<div class="row">

			<!-- Event Calendar -->
			<div class="col-xl-8 col-xxl-9 theiaStickySidebar">
				<div class="card">
					<div class="card-body">
						<div id="events"></div>
					</div>
				</div>
			</div>
			<!-- /Event Calendar -->

			<!-- Event List -->
			<div class="col-xl-4 col-xxl-3 theiaStickySidebar">
				<div class="d-flex align-items-center justify-content-between">
					<h5 class="mb-3">Events</h5>
					<div class="dropdown mb-3">
						<a href="javascript:void(0);" class="btn btn-outline-light dropdown-toggle"
							data-bs-toggle="dropdown">All Category
						</a>
						<ul class="dropdown-menu p-3">
							<li>
								<a href="#" class="dropdown-item rounded-1 d-flex align-items-center">
									<i class="ti ti-circle-filled fs-8 text-warning me-2"></i>Celebration
								</a>
							</li>
							<li>
								<a href="#" class="dropdown-item rounded-1 d-flex align-items-center">
									<i class="ti ti-circle-filled fs-8 text-success me-2"></i>Training
								</a>
							</li>
							<li>
								<a href="#" class="dropdown-item rounded-1 d-flex align-items-center">
									<i class="ti ti-circle-filled fs-8 text-info me-2"></i>Meeting
								</a>
							</li>
							<li>
								<a href="#" class="dropdown-item rounded-1 d-flex align-items-center">
									<i class="ti ti-circle-filled fs-8 text-danger me-2"></i>Holidays
								</a>
							</li>
							<li>
								<a href="#" class="dropdown-item rounded-1 d-flex align-items-center">
									<i class="ti ti-circle-filled fs-8 text-pending me-2"></i>Camp
								</a>
							</li>
						</ul>
					</div>	
				</div>

				<!-- Event Item -->
				<div class="border-start border-info border-3 shadow-sm p-3 mb-3 bg-white">
					<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
						<span class="avatar p-1 me-3 bg-primary-transparent flex-shrink-0">
							<i class="ti ti-users-group text-info fs-20"></i>
						</span>
						<div class="flex-fill">
							<h6 class="mb-1">Parents, Teacher Meet</h6>
							<p class="fs-12"><i class="ti ti-calendar me-1"></i>15 July 2024</p>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between">
						<p class="mb-0 fs-12"><i class="ti ti-clock me-1"></i>09:10AM - 10:50PM</p>
						<div class="avatar-list-stacked avatar-group-sm">
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-01.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-07.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-02.jpg')}}" class="rounded" alt="img">
							</span>
						</div>
					</div>
				</div>
				<!-- /Event Item -->

				<!-- Event Item -->
				<div class="border-start border-danger border-3 shadow-sm p-3 mb-3 bg-white">
					<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
						<span class="avatar p-1 me-2 bg-danger-transparent flex-shrink-0">
							<i class="ti ti-vacuum-cleaner fs-24"></i>
						</span>
						<div class="flex-fill">
							<h6 class="mb-1">Summer Vacation</h6>
							<p class="fs-12"><i class="ti ti-calendar me-1"></i>07 July 2024 - 08 July 2024</p>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between">
						<p class="fs-12 mb-0"><i class="ti ti-clock me-1"></i>09:10 AM - 10:50 PM</p>
						<div class="avatar-list-stacked avatar-group-sm">
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-11.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-13.jpg')}}" class="rounded" alt="img">
							</span>
						</div>
					</div>
				</div>
				<!-- /Event Item -->

				<!-- Event Item -->
				<div class="border-start border-info border-3 shadow-sm p-3 mb-3 bg-white">
					<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
						<span class="avatar p-1 me-2 bg-info-transparent flex-shrink-0">
							<i class="ti ti-user-edit fs-20"></i>
						</span>
						<div class="flex-fill">
							<h6 class="mb-1">Staff Meeting</h6>
							<p class="fs-12"><i class="ti ti-calendar me-1"></i>10 July 2024</p>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between">
						<p class="fs-12 mb-0"><i class="ti ti-clock me-1"></i>09:10AM - 10:50PM</p>
						<div class="avatar-list-stacked avatar-group-sm">
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-05.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-06.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-07.jpg')}}" class="rounded" alt="img">
							</span>
						</div>
					</div>
				</div>
				<!-- /Event Item -->

				<!-- Event Item -->
				<div class="border-start border-secondary border-3 shadow-sm p-3 mb-3 bg-white">
					<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
						<span class="avatar p-1 me-2 bg-secondary-transparent flex-shrink-0">
							<i class="ti ti-campfire fs-20"></i>
						</span>
						<div class="flex-fill">
							<h6 class="mb-1">Admission Camp</h6>
							<p class="fs-12"><i class="ti ti-calendar me-1"></i>10 July 2024</p>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between">
						<p class="fs-12 mb-0"><i class="ti ti-clock me-1"></i>09:10AM - 10:50PM</p>
						<div class="avatar-list-stacked avatar-group-sm">
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-04.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-05.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/parents/parent-10.jpg')}}" class="rounded" alt="img">
							</span>
						</div>
					</div>
				</div>
				<!-- /Event Item -->							

				<!-- Event Item -->
				<div class="border-start border-success border-3 shadow-sm p-3 mb-4 bg-white">
					<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
						<span class="avatar p-1 me-2 bg-success-transparent flex-shrink-0">
							<i class="ti ti-clipboard-heart fs-20"></i>
						</span>
						<div class="flex-fill">
							<h6 class="mb-1">Activity Training</h6>
							<p class="fs-12"><i class="ti ti-calendar me-1"></i>26 July 2024</p>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between">
						<p class="fs-12 mb-0"><i class="ti ti-clock me-1"></i>09:10AM - 10:50PM</p>
						<div class="avatar-list-stacked avatar-group-sm">
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" class="rounded" alt="img">
							</span>
							<span class="avatar border-0">
								<img src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" class="rounded" alt="img">
							</span>
						</div>
					</div>
				</div>
				<!-- /Event Item -->

			</div>
			<!-- /Event List -->

		</div>

	</div>
</div>
<!-- /Page Wrapper -->
    @component('components.modal-popup')
    @endcomponent
@endsection
