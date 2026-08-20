<?php $page = 'chat'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper chat-page-wrapper">
    <div class="content">

        <!-- sidebar group -->
        <div class="sidebar-group left-sidebar chat_sidebar">

            <!-- Chats sidebar -->
            <div id="chats" class="left-sidebar-wrap sidebar active slimscroll">

                <div class="slimscroll-active-sidebar">

                    <!-- Left Chat Title -->
                    <div class="left-chat-title all-chats d-flex justify-content-between align-items-center">
                        <div class="setting-title-head">
                            <h4> All Chats</h4>
                        </div>
                        <div class="add-section">
                            <ul>
                                <li><a href="javascript:void(0);" class="user-chat-search-btn"><i
                                            class="bx bx-search"></i></a></li>
                                <li>
                                    <div class="chat-action-btns">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="javascript:void(0);" class="dropdown-item "
                                                    data-bs-toggle="modal" data-bs-target="#new-chat"><span><i
                                                            class="bx bx-message-rounded-add"></i></span>New
                                                    Chat </a>
                                                <a href="javascript:void(0);" class="dropdown-item"
                                                    data-bs-toggle="modal" data-bs-target="#new-group"><span><i
                                                            class="bx bx-user-circle"></i></span>Create
                                                    Group</a>
                                                <a href="javascript:void(0);" class="dropdown-item"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#invite-other"><span><i
                                                            class="bx bx-user-plus"></i></span>Invite Others</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <!-- Chat Search -->
                            <div class="user-chat-search">
                                <form>
                                    <span class="form-control-feedback"><i class="bx bx-search"></i></span>
                                    <input type="text" name="chat-search" placeholder="Search"
                                        class="form-control">
                                    <div class="user-close-btn-chat"><i class="fa fa-close"></i></div>
                                </form>
                            </div>
                            <!-- /Chat Search -->
                        </div>
                    </div>
                    <!-- /Left Chat Title -->

                    <!-- Top Online Contacts -->
                    <div class="top-online-contacts p-4 pb-0">
                        <div class="fav-title">
                            <h5>Online Now</h5>
                            <a href="javascript:void(0);">View All</a>
                        </div>
                        <div class="swiper-container">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="avatar avatar-lg avatar-online">
                                        <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle"
                                            alt="Img">
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="avatar avatar-lg avatar-online">
                                        <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="rounded-circle"
                                            alt="Img">
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="avatar avatar-lg avatar-online">
                                        <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" class="rounded-circle"
                                            alt="Img">
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="avatar avatar-lg avatar-online">
                                        <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="rounded-circle"
                                            alt="Img">
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="avatar avatar-lg avatar-online">
                                        <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" class="rounded-circle"
                                            alt="Img">
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="avatar avatar-lg avatar-online">
                                        <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle"
                                            alt="Img">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Top Online Contacts -->

                    <div class="sidebar-body chat-body" id="chatsidebar">

                        <!-- Left Chat Title -->
                        <h5 class="mb-3">Pinned Chat</h5>
                        <!-- /Left Chat Title -->

                        <ul class="mb-3">
                            <li class="user-list-item">
                                <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar  avatar-lg avatar-online me-2 flex-shrink-0">
                                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle"
                                                alt="image">
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden me-2">
                                            <h6 class="mb-1 text-truncate">Mark Villiams</h6>
                                            <p class="text-truncate">Have you called them?</p>
                                        </div>
                                        <div class="flex-shrink-0 align-self-start text-end">
                                            <small class="text-muted">10:20 PM</small>
                                            <div class="chat-pin">
                                                <i class="bx bx-pin me-2"></i>
                                                <i class="bx bx-check-double"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li class="user-list-item">
                                <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar  avatar-lg me-2 flex-shrink-0">
                                            <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class="rounded-circle"
                                                alt="image">
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden me-2">
                                            <h6 class="mb-1 text-truncate">Elizabeth Sosa</h6>
                                            <p class="text-truncate"><span class="animate-typing-col">Typing
                                                    <span class="dot"></span>
                                                    <span class="dot"></span>
                                                    <span class="dot"></span>
                                                </span></p>
                                        </div>
                                        <div class="flex-shrink-0 align-self-start text-end">
                                            <small class="text-muted">Yesterday</small>
                                            <div class="chat-pin">
                                                <i class="bx bx-pin me-2"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <li class="user-list-item">
                                <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar  avatar-lg avatar-online me-2 flex-shrink-0">
                                            <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="rounded-circle"
                                                alt="image">
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden me-2">
                                            <h6 class="mb-1 text-truncate">Michael Howard</h6>
                                            <p class="text-truncate">Thank you</p>
                                        </div>
                                        <div class="flex-shrink-0 align-self-start text-end">
                                            <small class="text-muted">10:20 PM</small>
                                            <div>
                                                <i class="bx bx-pin me-2"></i>
                                                <i class="bx bx-check-double"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                        <!-- Left Chat Title -->
                        <h5 class="mb-3">Recent Chat</h6>
                            <!-- /Left Chat Title -->
                            <ul class="user-list">
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg avatar-online me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">Horace Keene</h6>
                                                <p class="text-truncate">Have you called them?</p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">Just Now</small>
                                                <div>
                                                    <span
                                                        class="badge bg-primary rounded-circle p-1 fs-8">11</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg avatar-online me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">Hollis Tran</h6>
                                                <p class="text-truncate"><i class="bx bx-video me-1"></i>Video
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">Yesterday</small>
                                                <div>
                                                    <i class="bx bx-check-double"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg avatar-online me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-15.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">James Albert</h6>
                                                <p class="text-truncate">Project Tools.doc</p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">10:20 PM</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg avatar-online me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-09.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">Debra Jones</h6>
                                                <p class="text-truncate"><i
                                                        class="bx bx-microphone me-1"></i>Audio</p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">12:30 PM</small>
                                                <div>
                                                    <i class="bx bx-check-double text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg  me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">Dina Brown</h6>
                                                <p class="text-truncate">Have you called them?</p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">Yesterday</small>
                                                <div>
                                                    <i class="bx bx-microphone-off"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg  me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-08.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">Judy Mercer</h6>
                                                <p class="text-truncate text-danger"><i
                                                        class="bx bx-phone-incoming me-1"></i>Missed Call</p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">25/July/23</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="user-list-item">
                                    <a href="javascript:void(0);" class="p-2 border rounded d-block mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar  avatar-lg  me-2 flex-shrink-0">
                                                <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}"
                                                    class="rounded-circle" alt="image">
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden me-2">
                                                <h6 class="mb-1 text-truncate">Richard Ohare</h6>
                                                <p class="text-truncate"><i
                                                        class="bx bx-image-alt me-1"></i>Photo</p>
                                            </div>
                                            <div class="flex-shrink-0 align-self-start text-end">
                                                <small class="text-muted">27/06/24</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                    </div>

                </div>

            </div>
            <!-- / Chats sidebar -->
        </div>
        <!-- /Sidebar group -->

        <!-- Chat -->
        <div class="chat chat-messages" id="middle">
            <div class="slimscroll">
                <div class="chat-header">
                    <div class="user-details">
                        <div class="d-lg-none">
                            <ul class="list-inline mt-2 me-2">
                                <li class="list-inline-item">
                                    <a class="text-muted px-0 left_sides" href="#" data-chat="open">
                                        <i class="fas fa-arrow-left"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="avatar avatar-lg me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle" alt="image">
                        </div>
                        <div>
                            <h6>Mark Villiams</h6>
                            <small class="last-seen">
                                Last Seen at 07:15 PM
                            </small>
                        </div>
                    </div>
                    <div class="chat-options ">
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <a href="javascript:void(0)" class="btn btn-outline-light chat-search-btn"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Search">
                                    <i class="bx bx-search"></i>
                                </a>
                            </li>
                            <li class="list-inline-item">
                                <a href="{{url('video-call')}}" class="btn btn-outline-light" data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" title="Video Call">
                                    <i class="bx bx-video"></i>
                                </a>
                            </li>
                            <li class="list-inline-item">
                                <a href="{{url('call')}}" class="btn btn-outline-light" data-bs-toggle="tooltip"
                                    data-bs-placement="bottom" title="Voice Call">
                                    <i class="bx bx-phone"></i>
                                </a>
                            </li>
                            <li class="list-inline-item dream_profile_menu">
                                <a href="javascript:void(0)" class="btn btn-outline-light not-chat-user">
                                    <i class="bx bx-info-circle"></i>
                                </a>
                            </li>
                            <li class="list-inline-item">
                                <a class="btn btn-outline-light no-bg" href="#" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="#" class="dropdown-item "><span><i class="bx bx-x"></i></span>Close
                                        Chat </a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-volume-mute"></i></span>Mute Notification</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-time-five"></i></span>Disappearing Message</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-brush-alt"></i></span>Clear Message</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-trash-alt"></i></span>Delete Chat</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-dislike"></i></span>Report</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-block"></i></span>Block</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <!-- Chat Search -->
                    <div class="chat-search">
                        <form>
                            <span class="form-control-feedback"><i class="bx bx-search"></i></span>
                            <input type="text" name="chat-search" placeholder="Search Chats"
                                class="form-control">
                            <div class="close-btn-chat"><i class="fa fa-close"></i></div>
                        </form>
                    </div>
                    <!-- /Chat Search -->
                </div>
                <div class="chat-body">
                    <div class="messages">
                        <div class="chats">
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                            <div class="chat-content">
                                <div class="chat-profile-name">
                                    <h6>Mark Villiams<span>8:16 PM</span></h6>
                                    <div class="chat-action-btns ms-3">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu chat-drop-menu dropdown-menu-end">
                                                <a href="#" class="dropdown-item message-info-left"><span><i
                                                            class="bx bx-info-circle"></i></span>Message Info
                                                </a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-share"></i></span>Reply</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-smile"></i></span>React</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-reply"></i></span>Forward</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-star"></i></span>Star Message</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-dislike"></i></span>Report</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-trash"></i></span>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="message-content">
                                    Hello <a href="javascript:void(0);">@Alex</a> Thank you for the beautiful
                                    web design ahead schedule.
                                    <div class="emoj-group">
                                        <ul>
                                            <li class="emoj-action"><a href="javascript:void(0);"><i
                                                        class="bx bx-smile"></i></a>
                                                <div class="emoj-group-list">
                                                    <ul>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li class="add-emoj"><a href="javascript:void(0);"><i
                                                                    class="bx bx-plus"></i></a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li><a href="#"><i class="bx bx-share"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chat-line">
                            <span class="chat-date">Today, July 24</span>
                        </div>
                        <div class="chats chats-right">
                            <div class="chat-content">
                                <div class="chat-profile-name text-end">
                                    <h6>Alex Smith<span>8:16 PM</span></h6>
                                    <div class="chat-action-btns ms-3">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu chat-drop-menu dropdown-menu-end">
                                                <a href="#" class="dropdown-item message-info-left"><span><i
                                                            class="bx bx-info-circle"></i></span>Message Info
                                                </a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-share"></i></span>Reply</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-smile"></i></span>React</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-reply"></i></span>Forward</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-star"></i></span>Star Message</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-dislike"></i></span>Report</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-trash"></i></span>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="message-content ">
                                    <div class="emoj-group rig-emoji-group">
                                        <ul>
                                            <li class="emoj-action"><a href="javascript:void(0);"><i
                                                        class="bx bx-smile"></i></a>
                                                <div class="emoj-group-list">
                                                    <ul>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li class="add-emoj"><a href="javascript:void(0);"><i
                                                                    class="bx bx-plus"></i></a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li><a href="#"><i class="bx bx-share"></i></a></li>
                                        </ul>
                                    </div>
                                    <div class="chat-voice-group">
                                        <ul>
                                            <li><a href="javascript:void(0);"><span><img
                                                            src="{{URL::asset('build/img/icons/play-01.svg')}}"
                                                            alt="image"></span></a></li>
                                            <li><img src="{{URL::asset('build/img/icons/voice.svg')}}" alt="image"></li>
                                            <li>0:05</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                        </div>
                        <div class="chats">
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                            <div class="chat-content">
                                <div class="chat-profile-name">
                                    <h6>Mark Villiams<span>8:16 PM</span><span class="check-star"><i
                                                class="bx bxs-star"></i></span></h6>
                                    <div class="chat-action-btns ms-2">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu chat-drop-menu dropdown-menu-end">
                                                <a href="#" class="dropdown-item message-info-left"><span><i
                                                            class="bx bx-info-circle"></i></span>Message Info
                                                </a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-share"></i></span>Reply</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-smile"></i></span>React</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-reply"></i></span>Forward</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bxs-star"></i></span>Unstar Message</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-dislike"></i></span>Report</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-trash"></i></span>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="message-content award-link chat-award-link">
                                    <a href="javascript:void(0);"
                                        class="mb-1">https://www.youtube.com/watch?v=GCmL3mS0Psk</a>
                                    <img src="{{URL::asset('build/img/sending-img.png')}}" alt="img">
                                    <div class="emoj-group">
                                        <ul>
                                            <li class="emoj-action"><a href="javascript:void(0);"><i
                                                        class="bx bx-smile"></i></a>
                                                <div class="emoj-group-list">
                                                    <ul>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li class="add-emoj"><a href="javascript:void(0);"><i
                                                                    class="bx bx-plus"></i></a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li><a href="#"><i class="bx bx-share"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chats chats-right">
                            <div class="chat-content">
                                <div class="chat-profile-name justify-content-end">
                                    <h6>Alex Smith<span>8:16 PM</span></h6>
                                    <div class="chat-action-btns ms-3">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu chat-drop-menu dropdown-menu-end">
                                                <a href="#" class="dropdown-item message-info-left"><span><i
                                                            class="bx bx-info-circle"></i></span>Message Info
                                                </a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-share"></i></span>Reply</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-smile"></i></span>React</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-reply"></i></span>Forward</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-star"></i></span>Star Message</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-dislike"></i></span>Report</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-trash"></i></span>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="message-content fancy-msg-box">
                                    <div class="emoj-group wrap-emoji-group ">
                                        <ul>
                                            <li class="emoj-action"><a href="javascript:void(0);"><i
                                                        class="bx bx-smile"></i></a>
                                                <div class="emoj-group-list">
                                                    <ul>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li class="add-emoj"><a href="javascript:void(0);"><i
                                                                    class="bx bx-plus"></i></a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li><a href="javascript:void(0);"><i class="bx bx-share"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="download-col">
                                        <ul class="nav mb-0">
                                            <li>
                                                <div class="image-download-col">
                                                    <a href="{{URL::asset('build/img/media/media-02.jpg')}}"
                                                        data-fancybox="gallery" class="fancybox">
                                                        <img src="{{URL::asset('build/img/media/media-02.jpg')}}" alt="Img">
                                                    </a>

                                                </div>
                                            </li>
                                            <li>
                                                <div class="image-download-col ">
                                                    <a href="{{URL::asset('build/img/media/media-03.jpg')}}"
                                                        data-fancybox="gallery" class="fancybox">
                                                        <img src="{{URL::asset('build/img/media/media-03.jpg')}}" alt="Img">
                                                    </a>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="image-download-col image-not-download">
                                                    <a href="{{URL::asset('build/img/media/media-01.jpg')}}"
                                                        data-fancybox="gallery" class="fancybox">
                                                        <img src="{{URL::asset('build/img/media/media-01.jpg')}}" alt="Img">
                                                        <span>10+</span>
                                                    </a>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-10.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                        </div>

                        <div class="chats">
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                            <div class="chat-content">
                                <div class="chat-profile-name">
                                    <h6>Mark Villiams<span>8:16 PM</span></h6>
                                    <div class="chat-action-btns ms-3">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu chat-drop-menu dropdown-menu-end">
                                                <a href="#" class="dropdown-item message-info-left"><span><i
                                                            class="bx bx-info-circle"></i></span>Message Info
                                                </a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-share"></i></span>Reply</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-smile"></i></span>React</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-reply"></i></span>Forward</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-star"></i></span>Star Message</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-dislike"></i></span>Report</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-trash"></i></span>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="message-content review-files">
                                    <p class="d-flex align-items-center">Please check and review the files<span
                                            class="ms-1 d-flex"><img src="{{URL::asset('build/img/icons/smile-chat.svg')}}"
                                                alt="Icon"></span></p>
                                    <div class="file-download d-flex align-items-center mb-0">
                                        <div
                                            class="file-type d-flex align-items-center justify-content-center me-2">
                                            <i class="bx bxs-file-doc"></i>
                                        </div>
                                        <div class="file-details">
                                            <span class="file-name">Landing_page_V1.doc</span>
                                            <ul>
                                                <li>80 Bytes</li>
                                                <li><a href="javascript:void(0);">Download</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="emoj-group">
                                        <ul>
                                            <li class="emoj-action"><a href="javascript:void(0);"><i
                                                        class="bx bx-smile"></i></a>
                                                <div class="emoj-group-list">
                                                    <ul>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li class="add-emoj"><a href="javascript:void(0);"><i
                                                                    class="bx bx-plus"></i></a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li><a href="#"><i class="bx bx-share"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="like-chat-grp">
                                    <ul>
                                        <li class="like-chat"><a href="javascript:void(0);">2<img
                                                    src="{{URL::asset('build/img/icons/like.svg')}}" alt="Icon"></a></li>
                                        <li class="comment-chat"><a href="javascript:void(0);">2<img
                                                    src="{{URL::asset('build/img/icons/heart.svg')}}" alt="Icon"></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="chats">
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                            <div class="chat-content">
                                <div class="chat-profile-name">
                                    <h6>Mark Villiams<span>8:16 PM</span></h6>
                                    <div class="chat-action-btns ms-3">
                                        <div class="chat-action-col">
                                            <a class="#" href="#" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </a>
                                            <div class="dropdown-menu chat-drop-menu dropdown-menu-end">
                                                <a href="#" class="dropdown-item message-info-left"><span><i
                                                            class="bx bx-info-circle"></i></span>Message Info
                                                </a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-share"></i></span>Reply</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-smile"></i></span>React</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-reply"></i></span>Forward</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-star"></i></span>Star Message</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-dislike"></i></span>Report</a>
                                                <a href="#" class="dropdown-item"><span><i
                                                            class="bx bx-trash"></i></span>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="message-content">
                                    Thank you for your support
                                    <div class="emoj-group">
                                        <ul>
                                            <li class="emoj-action"><a href="javascript:void(0);"><i
                                                        class="bx bx-smile"></i></a>
                                                <div class="emoj-group-list">
                                                    <ul>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li><a href="javascript:void(0);"><img
                                                                    src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                                                    alt="Icon"></a></li>
                                                        <li class="add-emoj"><a href="javascript:void(0);"><i
                                                                    class="bx bx-plus"></i></a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            <li><a href="javascript:void(0);"><i class="bx bx-share"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="chats">
                            <div class="chat-avatar">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle dreams_chat"
                                    alt="image">
                            </div>
                            <div class="chat-content chat-cont-type">
                                <div class="chat-profile-name chat-type-wrapper">
                                    <p>Mark Villiams Typing...</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="chat-footer">
                <form>
                    <div class="smile-foot">
                        <div class="chat-action-btns">
                            <div class="chat-action-col">
                                <a class="action-circle" href="#" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="#" class="dropdown-item "><span><i
                                                class="bx bx-file"></i></span>Document</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-camera"></i></span>Camera</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-image"></i></span>Gallery</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-volume-full"></i></span>Audio</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-map"></i></span>Location</a>
                                    <a href="#" class="dropdown-item"><span><i
                                                class="bx bx-user-pin"></i></span>Contact</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="smile-foot emoj-action-foot">
                        <a href="#" class="action-circle"><i class="bx bx-smile"></i></a>
                        <div class="emoj-group-list-foot down-emoji-circle">
                            <ul>
                                <li><a href="javascript:void(0);"><img src="{{URL::asset('build/img/icons/emoj-icon-01.svg')}}"
                                            alt="Icon"></a></li>
                                <li><a href="javascript:void(0);"><img src="{{URL::asset('build/img/icons/emoj-icon-02.svg')}}"
                                            alt="Icon"></a></li>
                                <li><a href="javascript:void(0);"><img src="{{URL::asset('build/img/icons/emoj-icon-03.svg')}}"
                                            alt="Icon"></a></li>
                                <li><a href="javascript:void(0);"><img src="{{URL::asset('build/img/icons/emoj-icon-04.svg')}}"
                                            alt="Icon"></a></li>
                                <li><a href="javascript:void(0);"><img src="{{URL::asset('build/img/icons/emoj-icon-05.svg')}}"
                                            alt="Icon"></a></li>
                                <li class="add-emoj"><a href="javascript:void(0);"><i
                                            class="bx bx-plus"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="smile-foot">
                        <a href="#" class="action-circle"><i class="bx bx-microphone-off"></i></a>
                    </div>
                    <input type="text" class="form-control chat_form" placeholder="Type your message here...">
                    <div class="form-buttons">
                        <button class="btn send-btn" type="submit">
                            <i class="bx bx-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- /Chat -->

        <!-- Right sidebar -->
        <div class="right-sidebar right_sidebar_profile right-side-contact hide-right-sidebar" id="middle1">
            <div class="right-sidebar-wrap active">
                <div class="slimscroll">
                    <div
                        class="left-chat-title d-flex justify-content-between align-items-center border-bottom-0">
                        <div class="fav-title mb-0">
                            <h6>Contact Info</h6>
                        </div>
                        <div class="contact-close_call text-end">
                            <a href="#" class="close_profile close-star">
                                <i class="bx bxs-star"></i>
                            </a>
                            <a href="#" class="close_profile close-trash">
                                <i class="bx bx-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="sidebar-body">
                        <div class="mt-0 right_sidebar_logo">
                            <div class="text-center right-sidebar-profile">
                                <figure class="avatar avatar-xl mb-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class="rounded-circle"
                                        alt="image">
                                </figure>
                                <h5 class="profile-name text-truncate">Mark Villiams</h5>
                                <div class="last-seen-profile">
                                    <span>last seen at 07:15 PM</span>
                                </div>
                                <div class="chat-options chat-option-profile">
                                    <ul class="list-inline">
                                        <li class="list-inline-item">
                                            <a href="{{url('audio-call')}}" class="btn btn-outline-light "
                                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                title="Voice Call">
                                                <i class="bx bx-phone"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item ">
                                            <a href="{{url('video-call')}}" class="btn btn-outline-light profile-open"
                                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                title="Video Call">
                                                <i class="bx bx-video"></i>
                                            </a>
                                        </li>
                                        <li class="list-inline-item">
                                            <a href="javascript:void(0)" class="btn btn-outline-light"
                                                data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                title="Chat">
                                                <i class="bx bx-message-square-dots"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="chat-member-details">
                                <div class="member-details">
                                    <ul>
                                        <li>
                                            <h5>Bio</h5>
                                        </li>
                                        <li>
                                            <h6>Phone</h6>
                                            <span>555-555-21541</span>
                                        </li>
                                        <li>
                                            <h6>Email Address</h6>
                                            <span>info@example.com</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="right-sidebar-head share-media">
                        <div class="share-media-blk">
                            <h5>Shared Media</h5>
                            <a href="javascript:void(0);">View All</a>
                        </div>
                        <div class="about-media-tabs">
                            <nav>
                                <div class="nav nav-tabs " id="nav-tab">
                                    <a class="nav-item nav-link active" id="nav-home-tab" data-bs-toggle="tab"
                                        href="#info">Photos</a>
                                    <a class="nav-item nav-link" id="nav-profile-tab1" data-bs-toggle="tab"
                                        href="#Participants">Videos</a>
                                    <a class="nav-item nav-link" id="nav-profile-tab2" data-bs-toggle="tab"
                                        href="#media">File</a>
                                    <a class="nav-item nav-link" id="nav-profile-tab3" data-bs-toggle="tab"
                                        href="#link">Link</a>
                                </div>
                            </nav>
                            <div class="tab-content pt-0" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="info">
                                    <ul class="nav share-media-img mb-0">
                                        <li>
                                            <a href="{{URL::asset('build/img/media/media-01.jpg')}}" data-fancybox="gallery"
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-01.jpg')}}" alt="Img">
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{URL::asset('build/img/media/media-02.jpg')}}" data-fancybox="gallery"
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-02.jpg')}}" alt="Img">
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{URL::asset('build/img/media/media-03.jpg')}}" data-fancybox="gallery"
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-03.jpg')}}" alt="Img">
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{URL::asset('build/img/media/media-04.jpg')}}" data-fancybox="gallery"
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-04.jpg')}}" alt="Img">
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{URL::asset('build/img/media/media-05.jpg')}}" data-fancybox="gallery"
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-05.jpg')}}" alt="Img">
                                            </a>
                                        </li>
                                        <li class="blur-media">
                                            <a href="{{URL::asset('build/img/media/media-02.jpg')}}" data-fancybox="gallery"
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-02.jpg')}}" alt="Img">
                                            </a>
                                            <span>+10</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-pane fade" id="Participants">
                                    <ul class="nav share-media-img mb-0">
                                        <li>
                                            <a href="https://www.youtube.com/embed/Mj9WJJNp5wA" data-fancybox
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-01.jpg')}}" alt="img">
                                                <span><i class="bx bx-play-circle"></i></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.youtube.com/embed/Mj9WJJNp5wA" data-fancybox
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-02.jpg')}}" alt="img">
                                                <span><i class="bx bx-play-circle"></i></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.youtube.com/embed/Mj9WJJNp5wA" data-fancybox
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-03.jpg')}}" alt="img">
                                                <span><i class="bx bx-play-circle"></i></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.youtube.com/embed/Mj9WJJNp5wA" data-fancybox
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-04.jpg')}}" alt="img">
                                                <span><i class="bx bx-play-circle"></i></span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="https://www.youtube.com/embed/Mj9WJJNp5wA" data-fancybox
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-05.jpg')}}" alt="img">
                                                <span><i class="bx bx-play-circle"></i></span>
                                            </a>
                                        </li>
                                        <li class="blur-media">
                                            <a href="https://www.youtube.com/embed/Mj9WJJNp5wA" data-fancybox
                                                class="fancybox">
                                                <img src="{{URL::asset('build/img/media/media-03.jpg')}}" alt="img">
                                            </a>
                                            <span>+10</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-pane fade" id="media">
                                    <div class="media-file">
                                        <div class="media-doc-blk">
                                            <span><i class="bx bxs-file-doc"></i></span>
                                            <div class="document-detail">
                                                <h6>Landing_page_V1.doc</h6>
                                                <ul>
                                                    <li>12 Mar 2023</li>
                                                    <li>246.3 KB</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="media-download">
                                            <a href="javascript:void(0);"><i class="bx bx-download"></i></a>
                                        </div>
                                    </div>
                                    <div class="media-file">
                                        <div class="media-doc-blk">
                                            <span><i class="bx bxs-file-pdf"></i></span>
                                            <div class="document-detail">
                                                <h6>Design Guideless.pdf</h6>
                                                <ul>
                                                    <li>12 Mar 2023</li>
                                                    <li>246.3 KB</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="media-download">
                                            <a href="javascript:void(0);"><i class="bx bx-download"></i></a>
                                        </div>
                                    </div>
                                    <div class="media-file">
                                        <div class="media-doc-blk">
                                            <span><i class="bx bxs-file"></i></span>
                                            <div class="document-detail">
                                                <h6>sample site.txt</h6>
                                                <ul>
                                                    <li>12 Mar 2023</li>
                                                    <li>246.3 KB</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="media-download">
                                            <a href="javascript:void(0);"><i class="bx bx-download"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="link">
                                    <div class="media-link-grp">
                                        <div class="link-img">
                                            <a href="javascript:void(0);"><img
                                                    src="{{URL::asset('build/img/media/media-link-01.jpg')}}" alt="Img"></a>
                                        </div>
                                        <div class="media-link-detail">
                                            <h6><a href="javascript:void(0);">Digital Marketing Guide</a></h6>
                                            <span><a
                                                    href="javascript:void(0);">https://elements.envato.com/all-items/blog</a></span>
                                        </div>
                                    </div>
                                    <div class="media-link-grp mb-0">
                                        <div class="link-img">
                                            <a href="javascript:void(0);"><img
                                                    src="{{URL::asset('build/img/media/media-link-02.jpg')}}" alt="Img"></a>
                                        </div>
                                        <div class="media-link-detail">
                                            <h6><a href="javascript:void(0);">Blog Post</a></h6>
                                            <span><a
                                                    href="javascript:void(0);">https://elements.envato.com/blog-post-TXQ5FB8</a></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-message-grp">
                        <ul>
                            <li>
                                <a href="javascript:void(0);" class="star-message-left">
                                    <div class="stared-group">
                                        <span class="star-message"> <i class="bx bx-star"></i></span>
                                        <h6>Starred Messages</h6>
                                    </div>
                                    <div class="count-group">
                                        <span>10</span>
                                        <i class="bx bx-chevron-right"></i>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <div class="stared-group">
                                        <span class="mute-message"> <i class="bx bx-microphone-off"></i></span>
                                        <h6>Mute Notifications</h6>
                                    </div>
                                    <div class="count-group">
                                        <i class="bx bx-chevron-right"></i>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <div class="stared-group">
                                        <span class="block-message"> <i class="bx bx-x-circle"></i></span>
                                        <h6>Block User</h6>
                                    </div>
                                    <div class="count-group">
                                        <i class="bx bx-chevron-right"></i>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <div class="stared-group">
                                        <span class="report-message"> <i class="bx bx-user-x"></i></span>
                                        <h6>Report User</h6>
                                    </div>
                                    <div class="count-group">
                                        <i class="bx bx-chevron-right"></i>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);">
                                    <div class="stared-group">
                                        <span class="delete-message"> <i class="bx bx-trash-alt"></i></span>
                                        <h6>Delete Chat</h6>
                                    </div>
                                    <div class="count-group">
                                        <i class="bx bx-chevron-right"></i>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Right sidebar -->

    </div>
</div>

    @component('components.modal-popup')
    @endcomponent
@endsection
