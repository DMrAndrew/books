
export const compareDates = (date1, date2, asc = true) =>
    date1.isAfter(date2) ? (asc ? 1 : -1) :
        (date2.isAfter(date1) ? (asc ? -1 : 1) : 0)

export const thread_point = (thread_id) => '/threads/' + thread_id + '/';
export const messages_point = (thread_id) => thread_point(thread_id) + 'messages/';
export const heartbeat_point = () => '/heartbeat/';