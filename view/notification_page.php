<!-- import React, { useState, useEffect } from 'react';
import { Bell, X } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Badge } from "@/components/ui/badge";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Button } from "@/components/ui/button";

const NotificationPage = () => {
  const [notifications, setNotifications] = useState([
    // your existing notification data
  ]);

  const [appointments, setAppointments] = useState([]);
  const [isOpen, setIsOpen] = useState(false);
  const [filter, setFilter] = useState('all');
  const unreadCount = notifications.filter(n => !n.isRead).length;

  useEffect(() => {
    // Fetch appointments data from backend
    fetch('/api/appointments')
      .then(response => response.json())
      .then(data => setAppointments(data))
      .catch(error => console.error('Error fetching appointments:', error));
  }, []);

  const filteredNotifications = notifications.filter(notification => {
    if (filter === 'all') return true;
    return notification.type === filter;
  });

  const markAsRead = (id) => {
    setNotifications(notifications.map(notification =>
      notification.id === id ? { ...notification, isRead: true } : notification
    ));
  };

  const clearAll = () => {
    setNotifications([]);
  };

  const getTypeColor = (type) => {
    return type === 'patient' ? 'bg-blue-100' : 'bg-green-100';
  };

  return (
    <div className="p-4 max-w-2xl mx-auto">
      <Card className="w-full">
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
          <div>
            <CardTitle className="text-2xl font-bold">Notifications</CardTitle>
            <CardDescription>Stay updated with your appointments</CardDescription>
          </div>
          
          <div className="flex items-center gap-4">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  Filter: {filter.charAt(0).toUpperCase() + filter.slice(1)}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuItem onClick={() => setFilter('all')}>All</DropdownMenuItem>
                <DropdownMenuItem onClick={() => setFilter('patient')}>Patient</DropdownMenuItem>
                <DropdownMenuItem onClick={() => setFilter('doctor')}>Doctor</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <div className="relative">
              <Button
                variant="outline"
                size="icon"
                className="relative"
                onClick={() => setIsOpen(!isOpen)}
              >
                <Bell className="h-5 w-5" />
                {unreadCount > 0 && (
                  <Badge className="absolute -top-2 -right-2 h-5 w-5 flex items-center justify-center p-0">
                    {unreadCount}
                  </Badge>
                )}
              </Button>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          {isOpen && (
            <div className="mt-2">
              <div className="flex justify-between items-center mb-4">
                <span className="text-sm text-gray-500">
                  {unreadCount} unread notifications
                </span>
                <Button variant="ghost" size="sm" onClick={clearAll}>
                  Clear all
                </Button>
              </div>

              <ScrollArea className="h-[400px] pr-4">
                {filteredNotifications.length > 0 ? (
                  <div className="space-y-4">
                    {filteredNotifications.map((notification) => (
                      <div
                        key={notification.id}
                        className={`p-4 rounded-lg ${getTypeColor(notification.type)} ${
                          !notification.isRead ? 'border-l-4 border-blue-500' : ''
                        }`}
                      >
                        <div className="flex justify-between items-start">
                          <div className="flex-1">
                            <p className="font-medium">{notification.message}</p>
                            <div className="flex items-center mt-2 text-sm text-gray-500">
                              <span>{notification.time}</span>
                              <Badge className="ml-2" variant="outline">
                                {notification.type}
                              </Badge>
                            </div>
                          </div>
                          
                          {!notification.isRead && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => markAsRead(notification.id)}
                            >
                              <X className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-8 text-gray-500">
                    No notifications to display
                  </div>
                )}
              </ScrollArea>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Appointments Section */}
      <Card className="w-full mt-4">
        <CardHeader>
          <CardTitle className="text-2xl font-bold">Appointments</CardTitle>
        </CardHeader>

        <CardContent>
          <ScrollArea className="h-[400px] pr-4">
            {appointments.length > 0 ? (
              <div className="space-y-4">
                {appointments.map((appointment) => (
                  <div
                    key={appointment.id}
                    className="p-4 rounded-lg bg-gray-100 border-l-4 border-gray-500"
                  >
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <p className="font-medium">Appointment with Dr. {appointment.doctor_name}</p>
                        <p className="text-sm text-gray-500">{appointment.appointment_time}</p>
                        <Badge className="mt-2" variant="outline">
                          {appointment.status}
                        </Badge>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                No appointments to display
              </div>
            )}
          </ScrollArea>
        </CardContent>
      </Card>
    </div>
  );
};

export default NotificationPage; -->
