"use client"

import { useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { QrCode, Users, Clock, Mail, UserPlus, Camera, BarChart3, Cross, Heart } from "lucide-react"

export default function AttendanceSystem() {
  const [currentTime, setCurrentTime] = useState(new Date())
  const [recentScans, setRecentScans] = useState([
    { id: 1, studentName: "Maria Santos", rollNumber: "ST001", time: "07:15 AM", status: "Present" },
    { id: 2, studentName: "Juan Dela Cruz", rollNumber: "ST002", time: "07:18 AM", status: "Present" },
    { id: 3, studentName: "Ana Rodriguez", rollNumber: "ST003", time: "07:45 AM", status: "Late" },
  ])
  const [stats, setStats] = useState({
    totalStudents: 450,
    presentToday: 432,
    absentToday: 18,
    lateToday: 12,
  })

  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000)
    return () => clearInterval(timer)
  }, [])

  return (
    <div className="min-h-screen bg-gradient-to-br from-cyan-50 to-blue-50">
      <header className="school-header">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center">
            <div className="flex items-center space-x-4">
              <div className="bg-white/20 p-3 rounded-full">
                <Cross className="h-8 w-8 text-white" />
              </div>
              <div className="text-left">
                <h1 className="text-2xl font-bold text-white">School of St. Maximilian Mary Kolbe</h1>
                <p className="text-cyan-100 text-lg">Attendance Management System</p>
                <p className="text-cyan-200 text-sm flex items-center gap-2">
                  <Heart className="h-4 w-4" />
                  Serving with Faith, Excellence, and Love
                </p>
              </div>
            </div>
            <div className="text-right">
              <p className="text-cyan-100 text-sm font-medium">
                {currentTime.toLocaleDateString("en-US", {
                  weekday: "long",
                  year: "numeric",
                  month: "long",
                  day: "numeric",
                })}
              </p>
              <p className="text-2xl font-bold text-white">{currentTime.toLocaleTimeString()}</p>
            </div>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="stats-grid">
          <div className="stat-card">
            <div className="flex items-center justify-between">
              <div>
                <span className="stat-number">{stats.totalStudents}</span>
                <p className="stat-label">Total Students</p>
              </div>
              <Users className="h-8 w-8 text-primary" />
            </div>
          </div>

          <div className="stat-card">
            <div className="flex items-center justify-between">
              <div>
                <span className="stat-number text-emerald-600">{stats.presentToday}</span>
                <p className="stat-label">Present Today</p>
              </div>
              <BarChart3 className="h-8 w-8 text-emerald-600" />
            </div>
          </div>

          <div className="stat-card">
            <div className="flex items-center justify-between">
              <div>
                <span className="stat-number text-red-600">{stats.absentToday}</span>
                <p className="stat-label">Absent Today</p>
              </div>
              <Clock className="h-8 w-8 text-red-600" />
            </div>
          </div>

          <div className="stat-card">
            <div className="flex items-center justify-between">
              <div>
                <span className="stat-number text-amber-600">{stats.lateToday}</span>
                <p className="stat-label">Late Today</p>
              </div>
              <Mail className="h-8 w-8 text-amber-600" />
            </div>
          </div>
        </div>

        {/* Main Content */}
        <Tabs defaultValue="scanner" className="space-y-6">
          <TabsList className="navigation-tabs">
            <TabsTrigger value="scanner" className="nav-tab">
              <Camera className="h-4 w-4" />
              <span>QR Scanner</span>
            </TabsTrigger>
            <TabsTrigger value="students" className="nav-tab">
              <Users className="h-4 w-4" />
              <span>Students</span>
            </TabsTrigger>
            <TabsTrigger value="attendance" className="nav-tab">
              <BarChart3 className="h-4 w-4" />
              <span>Attendance</span>
            </TabsTrigger>
            <TabsTrigger value="reports" className="nav-tab">
              <Mail className="h-4 w-4" />
              <span>Reports</span>
            </TabsTrigger>
          </TabsList>

          {/* QR Scanner Tab */}
          <TabsContent value="scanner">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <Card className="attendance-card">
                <CardHeader>
                  <CardTitle className="flex items-center space-x-2 text-card-foreground">
                    <Camera className="h-5 w-5 text-primary" />
                    <span>QR Code Scanner</span>
                  </CardTitle>
                  <CardDescription>Scan student QR codes to mark attendance</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="qr-scanner-container">
                    <Camera className="h-16 w-16 text-muted-foreground mb-4" />
                    <p className="text-muted-foreground mb-4">Camera will appear here</p>
                    <Button className="btn btn-primary">Start Camera</Button>
                    <p className="text-sm text-muted-foreground mt-4">Position the QR code within the camera frame</p>
                  </div>
                </CardContent>
              </Card>

              <Card className="attendance-card">
                <CardHeader>
                  <CardTitle className="text-card-foreground">Recent Scans</CardTitle>
                  <CardDescription>Latest attendance entries</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {recentScans.map((scan) => (
                      <div key={scan.id} className="flex items-center justify-between p-3 bg-muted rounded-lg">
                        <div>
                          <p className="font-medium text-card-foreground">{scan.studentName}</p>
                          <p className="text-sm text-muted-foreground">{scan.rollNumber}</p>
                        </div>
                        <div className="text-right">
                          <Badge
                            className={`status-badge ${
                              scan.status === "Present"
                                ? "status-present"
                                : scan.status === "Late"
                                  ? "status-late"
                                  : "status-absent"
                            }`}
                          >
                            {scan.status}
                          </Badge>
                          <p className="text-sm text-muted-foreground mt-1">{scan.time}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Students Tab */}
          <TabsContent value="students">
            <Card className="attendance-card">
              <CardHeader>
                <div className="flex justify-between items-center">
                  <div>
                    <CardTitle className="text-card-foreground">Student Management</CardTitle>
                    <CardDescription>Add and manage student records</CardDescription>
                  </div>
                  <Button className="btn btn-primary">
                    <UserPlus className="h-4 w-4 mr-2" />
                    Add Student
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="action-buttons">
                    <Input placeholder="Search students..." className="form-input flex-1" />
                    <Button className="btn btn-outline">Search</Button>
                  </div>

                  <div className="data-table-container">
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Roll Number</th>
                          <th>Name</th>
                          <th>Grade & Section</th>
                          <th>Parent Email</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>ST001</td>
                          <td>Maria Santos</td>
                          <td>Grade 10-A</td>
                          <td>maria.parent@email.com</td>
                          <td>
                            <div className="flex gap-2">
                              <Button size="sm" className="btn-outline">
                                <QrCode className="h-3 w-3 mr-1" />
                                QR Code
                              </Button>
                              <Button size="sm" className="btn-outline">
                                Edit
                              </Button>
                            </div>
                          </td>
                        </tr>
                        <tr>
                          <td>ST002</td>
                          <td>Juan Dela Cruz</td>
                          <td>Grade 10-B</td>
                          <td>juan.parent@email.com</td>
                          <td>
                            <div className="flex gap-2">
                              <Button size="sm" className="btn-outline">
                                <QrCode className="h-3 w-3 mr-1" />
                                QR Code
                              </Button>
                              <Button size="sm" className="btn-outline">
                                Edit
                              </Button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Attendance Tab */}
          <TabsContent value="attendance">
            <Card className="attendance-card">
              <CardHeader>
                <CardTitle className="text-card-foreground">Attendance Records</CardTitle>
                <CardDescription>View and manage daily attendance</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="action-buttons">
                    <Input type="date" className="form-input w-48" />
                    <Input placeholder="Search by name or roll number..." className="form-input flex-1" />
                    <Button className="btn btn-outline">Filter</Button>
                    <Button className="btn btn-primary">Export to Excel</Button>
                  </div>

                  <div className="data-table-container">
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Roll Number</th>
                          <th>Name</th>
                          <th>Grade & Section</th>
                          <th>Check-in Time</th>
                          <th>Status</th>
                          <th>Parent Email</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>ST001</td>
                          <td>Maria Santos</td>
                          <td>Grade 10-A</td>
                          <td>07:15 AM</td>
                          <td>
                            <span className="status-badge status-present">Present</span>
                          </td>
                          <td className="text-emerald-600">✓ Sent</td>
                        </tr>
                        <tr>
                          <td>ST002</td>
                          <td>Juan Dela Cruz</td>
                          <td>Grade 10-B</td>
                          <td>07:18 AM</td>
                          <td>
                            <span className="status-badge status-present">Present</span>
                          </td>
                          <td className="text-emerald-600">✓ Sent</td>
                        </tr>
                        <tr>
                          <td>ST003</td>
                          <td>Ana Rodriguez</td>
                          <td>Grade 10-A</td>
                          <td>07:45 AM</td>
                          <td>
                            <span className="status-badge status-late">Late</span>
                          </td>
                          <td className="text-amber-600">✓ Sent</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Reports Tab */}
          <TabsContent value="reports">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <Card className="attendance-card">
                <CardHeader>
                  <CardTitle className="text-card-foreground">Email Notifications</CardTitle>
                  <CardDescription>Parent notification settings and logs</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="alert alert-success">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="font-medium">Email Service Status</p>
                          <p className="text-sm">All systems operational</p>
                        </div>
                        <Badge className="status-badge status-present">Active</Badge>
                      </div>
                    </div>

                    <div className="space-y-2">
                      <h4 className="font-medium text-card-foreground">Recent Email Activity</h4>
                      <div className="text-sm text-muted-foreground space-y-1">
                        <p>• 432 attendance emails sent today</p>
                        <p>• 12 late arrival notifications sent</p>
                        <p>• 0 failed deliveries</p>
                        <p>• 18 absence notifications pending</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="attendance-card">
                <CardHeader>
                  <CardTitle className="text-card-foreground">Generate Reports</CardTitle>
                  <CardDescription>Export attendance data and analytics</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="form-group">
                      <label className="form-label">Report Type</label>
                      <select className="form-input">
                        <option>Daily Attendance Report</option>
                        <option>Weekly Summary</option>
                        <option>Monthly Analytics</option>
                        <option>Student-wise Report</option>
                        <option>Parent Communication Log</option>
                      </select>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                      <div className="form-group">
                        <label className="form-label">From Date</label>
                        <Input type="date" className="form-input" />
                      </div>
                      <div className="form-group">
                        <label className="form-label">To Date</label>
                        <Input type="date" className="form-input" />
                      </div>
                    </div>

                    <Button className="btn btn-primary w-full">Generate & Download Report</Button>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  )
}
