{{--
    Theme customizer offcanvas — adapted from PreSkool `theme-script.js`.

    In the original theme this markup is injected by JavaScript (with broken
    relative image paths and an Envato "Buy Now" button). Here it is rendered
    server-side with correct asset() URLs; the behaviour (radio handlers,
    localStorage persistence, reset) lives in resources/js/backoffice/theme.js.

    Element IDs and input names are contracts with theme.js — do not rename.
--}}
{{-- Floating theme-customizer gear button — hidden on request.
     The #theme-setting offcanvas below stays available (theme.js still binds to it).
<div class="sidebar-contact">
    <div class="toggle-theme" data-bs-toggle="offcanvas" data-bs-target="#theme-setting">
        <i class="fa fa-cog fa-w-16 fa-spin"></i>
    </div>
</div>
--}}
<div class="sidebar-themesettings offcanvas offcanvas-end" id="theme-setting">
    <div class="offcanvas-header d-flex align-items-center justify-content-between bg-light-500">
        <div>
            <h4 class="mb-1">{{ __('Theme Customizer') }}</h4>
            <p>{{ __('Choose your themes & layouts etc.') }}</p>
        </div>
        <a href="#" class="custom-btn-close d-flex align-items-center justify-content-center text-white" data-bs-dismiss="offcanvas"><i class="ti ti-x"></i></a>
    </div>
    <div class="themesettings-inner offcanvas-body">
        <div class="accordion accordion-customicon1 accordions-items-seperate" id="settingtheme">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#layoutsetting" aria-expanded="true">
                        {{ __('Select Layouts') }}
                    </button>
                </h2>
                <div id="layoutsetting" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="row gx-3">
                            <div class="col-4">
                                <div class="theme-layout mb-3">
                                    <input type="radio" name="LayoutTheme" id="defaultLayout" value="default" checked>
                                    <label for="defaultLayout">
                                        <span class="d-block mb-2 layout-img">
                                            <img src="{{ asset('assets/preskool/img/theme/default.svg') }}" alt="img">
                                        </span>
                                        <span class="layout-type">{{ __('Default') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="theme-layout mb-3">
                                    <input type="radio" name="LayoutTheme" id="miniLayout" value="mini">
                                    <label for="miniLayout">
                                        <span class="d-block mb-2 layout-img">
                                            <img src="{{ asset('assets/preskool/img/theme/mini.svg') }}" alt="img">
                                        </span>
                                        <span class="layout-type">{{ __('Mini') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="theme-layout mb-3">
                                    <input type="radio" name="LayoutTheme" id="boxLayout" value="box">
                                    <label for="boxLayout">
                                        <span class="d-block mb-2 layout-img">
                                            <img src="{{ asset('assets/preskool/img/theme/box.svg') }}" alt="img">
                                        </span>
                                        <span class="layout-type">{{ __('Boxed') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#colorsetting" aria-expanded="true">
                        {{ __('Top Bar Color') }}
                    </button>
                </h2>
                <div id="colorsetting" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="d-flex align-items-center">
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="topbar" id="whiteTopbar" value="white" checked>
                                <label for="whiteTopbar" class="white-topbar"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="topbar" id="darkTopbar" value="dark">
                                <label for="darkTopbar" class="dark-topbar bg-dark"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="topbar" id="primaryTopbar" value="primary">
                                <label for="primaryTopbar" class="primary-topbar bg-primary"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1">
                                <input type="radio" name="topbar" id="greyTopbar" value="grey">
                                <label for="greyTopbar" class="grey-topbar bg-light"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#modesetting" aria-expanded="true">
                        {{ __('Color Mode') }}
                    </button>
                </h2>
                <div id="modesetting" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="row gx-3">
                            <div class="col-6">
                                <div class="theme-mode">
                                    <input type="radio" name="theme" id="lightTheme" value="light" checked>
                                    <label for="lightTheme" class="p-2 rounded fw-medium w-100">
                                        <span class="avatar avatar-md d-inline-flex rounded me-2"><i class="ti ti-sun-filled"></i></span>{{ __('Light Mode') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="theme-mode">
                                    <input type="radio" name="theme" id="darkTheme" value="dark">
                                    <label for="darkTheme" class="p-2 rounded fw-medium w-100">
                                        <span class="avatar avatar-md d-inline-flex rounded me-2"><i class="ti ti-moon-filled"></i></span>{{ __('Dark Mode') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarsetting" aria-expanded="true">
                        {{ __('Sidebar Color') }}
                    </button>
                </h2>
                <div id="sidebarsetting" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="d-flex align-items-center">
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="sidebar" id="lightSidebar" value="light" checked>
                                <label for="lightSidebar" class="d-block rounded mb-2"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="sidebar" id="darkSidebar" value="dark">
                                <label for="darkSidebar" class="d-block rounded bg-dark mb-2"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="sidebar" id="primarySidebar" value="primary">
                                <label for="primarySidebar" class="d-block rounded bg-primary mb-2"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1 me-3">
                                <input type="radio" name="sidebar" id="darkblackSidebar" value="darkblack">
                                <label for="darkblackSidebar" class="d-block rounded bg-darkblack mb-2"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded m-1">
                                <input type="radio" name="sidebar" id="darkblueSidebar" value="darkblue">
                                <label for="darkblueSidebar" class="d-block rounded bg-darkblue mb-2"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button text-dark fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarcolor" aria-expanded="true">
                        {{ __('Theme Colors') }}
                    </button>
                </h2>
                <div id="sidebarcolor" class="accordion-collapse collapse show">
                    <div class="accordion-body pb-2">
                        <div class="d-flex align-items-center">
                            <div class="custom-themecolor custom-themecolor-rounded me-3 mb-2">
                                <input type="radio" name="color" id="primaryColor" value="primary" checked>
                                <label for="primaryColor" class="bg-primary"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded me-3 mb-2">
                                <input type="radio" name="color" id="violetColor" value="violet">
                                <label for="violetColor" class="bg-violet"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded me-3 mb-2">
                                <input type="radio" name="color" id="pinkColor" value="pink">
                                <label for="pinkColor" class="bg-pink"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded me-3 mb-2">
                                <input type="radio" name="color" id="orangeColor" value="orange">
                                <label for="orangeColor" class="bg-orange"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded me-3 mb-2">
                                <input type="radio" name="color" id="greenColor" value="green">
                                <label for="greenColor" class="bg-green"></label>
                            </div>
                            <div class="custom-themecolor custom-themecolor-rounded me-3 mb-2">
                                <input type="radio" name="color" id="redColor" value="red">
                                <label for="redColor" class="bg-red"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-light-500 p-3">
        <div class="row gx-3">
            <div class="col-12">
                <a href="#" id="resetbutton" class="btn btn-light close-theme w-100">{{ __('Reset') }}</a>
            </div>
        </div>
    </div>
</div>
