import { useState } from "react";
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import PerfectScrollbar from "react-perfect-scrollbar";
import "../../../../node_modules/react-perfect-scrollbar/dist/css/styles.css";

const AudioCall = () => {
  const [addClass, setAddClass] = useState(false);
  const [mute, setMute] = useState(false);
  const handleShowClass = () => {
    setAddClass(true);
  };

  const handleShowremoveClass = () => {
    setAddClass(false);
  };

  return (
    <div className="page-wrapper">
      <div className="content">
        {/* Call list */}
        <div className="card">
          <div className="card-body">
            <div className="row">
              <div className="col-xl-12">
                <div className="conference-meet-group">
                  <div
                    className={
                      addClass ? "meeting-list add-meeting" : "meeting-list"
                    }
                  >
                    {/* Horizontal View */}
                    <div className="join-contents horizontal-view fade-whiteboard">
                      <div className="join-video audio-calls user-active">
                        <div className="audio-call-group">
                          <ul>
                            <li className="active">
                              <div className="avatar ">
                                <ImageWithBasePath
                                  src="assets/img/profiles/avatar-02.jpg"
                                  className="rounded-circle"
                                  alt="image"
                                />
                                <div className="more-icon">
                                  <Link to="#">
                                    <i className="feather feather-radio" />
                                  </Link>
                                </div>
                              </div>
                              <div className="user-audio-call">
                                <h5>Mark Villiams</h5>
                              </div>
                            </li>
                            <li>
                              <div className="avatar ">
                                <ImageWithBasePath
                                  src="assets/img/users/user-16.jpg"
                                  className="rounded-circle"
                                  alt="image"
                                />
                                <div className="more-icon audio-more-icon">
                                  <Link to="#" className="other-mic-off">
                                    <i className="bx bx-microphone" />
                                  </Link>
                                </div>
                              </div>
                              <div className="user-audio-call">
                                <h5>Benjamin</h5>
                              </div>
                            </li>
                          </ul>
                        </div>
                        <div className="record-time">
                          <span>40:12</span>
                        </div>
                        <div className="meet-drop meet-mutes-bottom">
                          <ul>
                            <li>
                              <Link
                                to="#"
                                id="show-message"
                                onClick={handleShowClass}
                              >
                                <i className="bx bx-message-alt-dots" />
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    {/* /Horizontal View */}
                  </div>
                  <div
                    className={
                      addClass
                        ? "audio right-user-side chat-rooms theiaStickySidebar mb-4 open-chats"
                        : "audio right-user-side chat-rooms theiaStickySidebar mb-4"
                    }
                    id="chat-room"
                  >
                 <PerfectScrollbar>
                    <div className="card slime-grp ">
                      <div className="card-header stickiy-header d-flex align-items-center justify-content-between pb-0">
                        <h5 className="mb-3">Message</h5>
                        <Link
                          to="#"
                          className="close_profile close_profile4 mb-3 link-danger"
                          onClick={handleShowremoveClass}
                        >
                          <i className="ti ti-x" />
                        </Link>
                      </div>
                      <div className="card-body slimscroll  p-0">
                        <div className="chat-msg-blk ">
                          <div className="chats">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>Hi Everyone.!</h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                          <div className="chats chats-right">
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>
                                  Good Morning..! Today we have meeting about
                                  the new product.
                                </h4>
                              </div>
                              <div className="chat-profile-name text-end">
                                <h6>
                                  <i className="bx bx-check-double" /> 10:00
                                </h6>
                              </div>
                            </div>
                            <div className="avatar avatar-lg flex-shrink-0 ms-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-02.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                          </div>
                          <div className="chats">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>Hi.! Good Morning all.</h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                          <div className="chats">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>Nice..which category it belongs to?</h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                          <div className="chats">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>
                                  Great.! This is the second new product that
                                  comes in this week.
                                </h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                          <div className="chats">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>Hi.! Good Morning all.</h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                          <div className="chats">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>Nice..which category it belongs to?</h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                          <div className="chats chats-right">
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>
                                  Good Morning..! Today we have meeting about
                                  the new product.
                                </h4>
                              </div>
                              <div className="chat-profile-name text-end">
                                <h6>
                                  <i className="bx bx-check-double" /> 10:00
                                </h6>
                              </div>
                            </div>
                            <div className="avatar avatar-lg flex-shrink-0 ms-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-02.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                          </div>
                          <div className="chats mb-0">
                            <div className="avatar avatar-lg flex-shrink-0 me-2">
                              <ImageWithBasePath
                                src="assets/img/users/user-01.jpg"
                                className="rounded"
                                alt="image"
                              />
                            </div>
                            <div className="chat-content">
                              <div className="message-content">
                                <h4>
                                  Great.! This is the second new product that
                                  comes in this week.
                                </h4>
                              </div>
                              <div className="chat-profile-name d-flex justify-content-end">
                                <h6>10:00 AM</h6>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div className="chat-footer">
                          <form>
                            <div className="smile-col comman-icon">
                              <Link to="#">
                                <i className="far fa-smile" />
                              </Link>
                            </div>
                            <div className="attach-col comman-icon">
                              <Link to="#">
                                <i className="fas fa-paperclip" />
                              </Link>
                            </div>
                            <div className="micro-col comman-icon">
                              <Link to="#">
                                <i className="bx bx-microphone" />
                              </Link>
                            </div>
                            <input
                              type="text"
                              className="form-control chat_form"
                              placeholder="Enter Message....."
                            />
                            <div className="send-chat comman-icon">
                              <Link to="#">
                                <i className="feather feather-send" />
                              </Link>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
               </PerfectScrollbar>
                  </div>
                </div>
                <div className="meet-call-menu-blk">
                  <div className="video-call-action">
                    <ul className="nav">
                      <li>
                        <Link to="#" className={`${mute? 'stop': ''} mute-bt `} onClick={()=>setMute(!mute)}>
                          <i className={`${mute?'bx-microphone-off':'bx-microphone'} bx `} />
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="call-end">
                          <i className="feather feather-phone" />
                        </Link>
                      </li>
                      <li>
                        <Link to="#">
                          <i className="bx bx-video-off" />
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        {/* /Call list */}
      </div>
    </div>
  );
};

export default AudioCall;
